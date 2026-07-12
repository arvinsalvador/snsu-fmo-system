<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\GenerateReportRequest;
use App\Http\Requests\Reports\SaveReportScheduleRequest;
use App\Http\Resources\Api\V1\GeneratedReportResource;
use App\Http\Resources\Api\V1\ReportScheduleResource;
use App\Http\Resources\Api\V1\ReportTemplateResource;
use App\Jobs\GenerateReportJob;
use App\Models\GeneratedReport;
use App\Models\ReportDeliveryLog;
use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use App\Services\ReportAuditService;
use App\Services\ReportGenerationService;
use App\Services\ReportScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportGenerationController extends Controller
{
    public function templates(Request $request)
    {
        Gate::authorize('viewAny', ReportTemplate::class);

        return ReportTemplateResource::collection(ReportTemplate::where('is_active', true)->orderBy('name')->paginate(20));
    }

    public function template(ReportTemplate $reportTemplate): ReportTemplateResource
    {
        Gate::authorize('view', $reportTemplate);

        return new ReportTemplateResource($reportTemplate);
    }

    public function generate(GenerateReportRequest $request): JsonResponse
    {
        $template = ReportTemplate::findOrFail($request->integer('report_template_id'));
        $format = $request->validated('output_format');
        if ($format) {
            $template = (clone $template)->forceFill(['output_format' => $format]);
        } $snapshot = GeneratedReport::create(['report_template_id' => $template->id, 'report_type' => $template->report_type, 'title' => $template->name, 'reporting_period_start' => $request->validated('date_from'), 'reporting_period_end' => $request->validated('date_to'), 'filters' => $request->safe()->except(['report_template_id', 'output_format']), 'file_format' => $template->output_format, 'generation_status' => 'pending', 'generated_by' => $request->user()->id, 'metadata' => ['requested_format' => $template->output_format]]);
        GenerateReportJob::dispatch($snapshot->id);

        return response()->json(['message' => 'Report generation queued.', 'data' => new GeneratedReportResource($snapshot)], 202);
    }

    public function generated(Request $request)
    {
        Gate::authorize('viewAny', GeneratedReport::class);
        $query = GeneratedReport::latest();
        if (! $request->user()->can('manage_report_schedules')) {
            $query->where('generated_by', $request->user()->id);
        }

        return GeneratedReportResource::collection($query->paginate(20));
    }

    public function show(GeneratedReport $generatedReport): GeneratedReportResource
    {
        Gate::authorize('view', $generatedReport);

        return new GeneratedReportResource($generatedReport);
    }

    public function download(GeneratedReport $generatedReport, ReportAuditService $audit): BinaryFileResponse
    {
        Gate::authorize('download', $generatedReport);
        abort_unless($generatedReport->generation_status === 'completed' && Storage::disk(config('reports.disk'))->exists($generatedReport->file_path), 404);
        $audit->record('downloaded', $generatedReport, metadata: ['channel' => 'api']);

        return response()->download(Storage::disk(config('reports.disk'))->path($generatedReport->file_path), $generatedReport->file_name, ['Content-Type' => $generatedReport->metadata['content_type'] ?? 'application/octet-stream']);
    }

    public function verify(GeneratedReport $generatedReport, ReportGenerationService $service): JsonResponse
    {
        Gate::authorize('view', $generatedReport);

        return response()->json(['data' => ['uuid' => $generatedReport->uuid, 'valid' => $service->verify($generatedReport), 'checksum' => $generatedReport->checksum]]);
    }

    public function schedules(Request $request)
    {
        Gate::authorize('viewAny', ReportSchedule::class);

        return ReportScheduleResource::collection(ReportSchedule::with(['template', 'recipients.user'])->latest()->paginate(20));
    }

    public function storeSchedule(SaveReportScheduleRequest $request, ReportScheduleService $service): JsonResponse
    {
        $data = $request->safe()->except('recipient_user_ids');
        $schedule = ReportSchedule::create([...$data, 'created_by' => $request->user()->id]);
        $schedule->update(['next_run_at' => $service->nextRun($schedule)]);
        $this->syncRecipients($schedule, $request->validated('recipient_user_ids', []), $request);

        return response()->json(['data' => new ReportScheduleResource($schedule->load(['template', 'recipients.user']))], 201);
    }

    public function schedule(ReportSchedule $reportSchedule): ReportScheduleResource
    {
        Gate::authorize('view', $reportSchedule);

        return new ReportScheduleResource($reportSchedule->load(['template', 'recipients.user']));
    }

    public function updateSchedule(SaveReportScheduleRequest $request, ReportSchedule $reportSchedule, ReportScheduleService $service): ReportScheduleResource
    {
        Gate::authorize('update', $reportSchedule);
        $reportSchedule->update([...$request->safe()->except('recipient_user_ids'), 'updated_by' => $request->user()->id, 'next_run_at' => $service->nextRun($request->validated())]);
        $this->syncRecipients($reportSchedule, $request->validated('recipient_user_ids', []), $request);

        return new ReportScheduleResource($reportSchedule->load(['template', 'recipients.user']));
    }

    public function destroySchedule(ReportSchedule $reportSchedule): JsonResponse
    {
        Gate::authorize('delete', $reportSchedule);
        $reportSchedule->delete();

        return response()->json(null, 204);
    }

    public function run(ReportSchedule $reportSchedule, Request $request, ReportScheduleService $service): JsonResponse
    {
        Gate::authorize('run', $reportSchedule);
        abort_if($reportSchedule->processing_key, 409, 'Schedule is already processing.');
        $filters = $service->filters($reportSchedule);
        $key = (string) Str::uuid();
        $reportSchedule->update(['processing_key' => $key]);
        $snapshot = GeneratedReport::create(['report_template_id' => $reportSchedule->report_template_id, 'report_type' => $reportSchedule->template->report_type, 'title' => $reportSchedule->name, 'reporting_period_start' => $filters['date_from'], 'reporting_period_end' => $filters['date_to'], 'filters' => $filters, 'file_format' => $reportSchedule->output_format, 'generation_status' => 'pending', 'generated_by' => $request->user()->id, 'metadata' => ['schedule_uuid' => $reportSchedule->uuid, 'processing_key' => $key]]);
        GenerateReportJob::dispatch($snapshot->id, $reportSchedule->id);

        return response()->json(['message' => 'Scheduled report queued.', 'data' => new GeneratedReportResource($snapshot)], 202);
    }

    public function toggle(ReportSchedule $reportSchedule, Request $request, ReportScheduleService $service): ReportScheduleResource
    {
        Gate::authorize('update', $reportSchedule);
        $enabled = $request->routeIs('api.v1.report-schedules.enable');
        $reportSchedule->update(['is_active' => $enabled, 'next_run_at' => $enabled ? $service->nextRun($reportSchedule) : null, 'processing_key' => null]);

        return new ReportScheduleResource($reportSchedule);
    }

    public function deliveries(ReportSchedule $reportSchedule)
    {
        Gate::authorize('viewAny', ReportDeliveryLog::class);

        return response()->json(['data' => ReportDeliveryLog::where('report_schedule_id', $reportSchedule->id)->latest()->paginate(20)]);
    }

    private function syncRecipients(ReportSchedule $schedule, array $ids, Request $request): void
    {
        $schedule->recipients()->delete();
        if ($request->validated('delivery_method') !== 'database_notification') {
            return;
        }
        foreach (array_unique($ids) as $id) {
            $schedule->recipients()->create(['user_id' => $id, 'delivery_channel' => 'database_notification', 'configured_by' => $request->user()->id]);
        }
    }
}
