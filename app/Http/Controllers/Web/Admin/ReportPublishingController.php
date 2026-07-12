<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\GenerateReportRequest;
use App\Http\Requests\Reports\SaveReportScheduleRequest;
use App\Http\Requests\Reports\SaveReportTemplateRequest;
use App\Jobs\GenerateReportJob;
use App\Models\GeneratedReport;
use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use App\Models\User;
use App\Services\ReportAuditService;
use App\Services\ReportGenerationService;
use App\Services\ReportScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportPublishingController extends Controller
{
    public function generateForm(): View
    {
        Gate::authorize('create', GeneratedReport::class);

        return view('admin.reports.publish.generate', ['templates' => ReportTemplate::where('is_active', true)->orderBy('name')->get()]);
    }

    public function generate(GenerateReportRequest $request): RedirectResponse
    {
        $template = ReportTemplate::findOrFail($request->integer('report_template_id'));
        $snapshot = GeneratedReport::create(['report_template_id' => $template->id, 'report_type' => $template->report_type, 'title' => $template->name, 'reporting_period_start' => $request->validated('date_from'), 'reporting_period_end' => $request->validated('date_to'), 'filters' => $request->safe()->except(['report_template_id', 'output_format']), 'file_format' => $request->validated('output_format') ?: $template->output_format, 'generation_status' => 'pending', 'generated_by' => $request->user()->id]);
        GenerateReportJob::dispatch($snapshot->id);

        return redirect()->route('admin.reports.generated.show', $snapshot)->with('status', 'Report generation queued.');
    }

    public function generated(Request $request): View
    {
        Gate::authorize('viewAny', GeneratedReport::class);
        $query = GeneratedReport::with('template')->latest();
        if (! $request->user()->can('manage_report_schedules')) {
            $query->where('generated_by', $request->user()->id);
        }

        return view('admin.reports.publish.generated', ['reports' => $query->paginate(20)]);
    }

    public function show(GeneratedReport $generatedReport, ReportGenerationService $service): View
    {
        Gate::authorize('view', $generatedReport);

        return view('admin.reports.publish.show', ['report' => $generatedReport->load(['template', 'generator', 'deliveries']), 'integrity' => $generatedReport->generation_status === 'completed' ? $service->verify($generatedReport) : null]);
    }

    public function download(GeneratedReport $generatedReport, ReportAuditService $audit): BinaryFileResponse
    {
        Gate::authorize('download', $generatedReport);
        abort_unless($generatedReport->file_path && Storage::disk(config('reports.disk'))->exists($generatedReport->file_path), 404);
        $audit->record('downloaded', $generatedReport, metadata: ['channel' => 'web']);

        return response()->download(Storage::disk(config('reports.disk'))->path($generatedReport->file_path), $generatedReport->file_name);
    }

    public function print(GeneratedReport $generatedReport)
    {
        Gate::authorize('view', $generatedReport);
        abort_unless($generatedReport->file_format === 'print_view' && Storage::disk(config('reports.disk'))->exists($generatedReport->file_path), 404);

        return response(Storage::disk(config('reports.disk'))->get($generatedReport->file_path))->header('Content-Type', 'text/html');
    }

    public function regenerate(GeneratedReport $generatedReport, Request $request): RedirectResponse
    {
        Gate::authorize('view', $generatedReport);
        Gate::authorize('create', GeneratedReport::class);
        $copy = GeneratedReport::create(['report_template_id' => $generatedReport->report_template_id, 'report_type' => $generatedReport->report_type, 'title' => $generatedReport->title, 'reporting_period_start' => $generatedReport->reporting_period_start, 'reporting_period_end' => $generatedReport->reporting_period_end, 'filters' => $generatedReport->filters, 'file_format' => $generatedReport->file_format, 'generation_status' => 'pending', 'generated_by' => $request->user()->id, 'metadata' => ['regenerated_from' => $generatedReport->uuid]]);
        GenerateReportJob::dispatch($copy->id);

        return redirect()->route('admin.reports.generated.show', $copy)->with('status', 'Report regeneration queued.');
    }

    public function archive(GeneratedReport $generatedReport): RedirectResponse
    {
        Gate::authorize('delete', $generatedReport);
        $generatedReport->delete();

        return redirect()->route('admin.reports.generated.index')->with('status', 'Report archived.');
    }

    public function templates(): View
    {
        Gate::authorize('viewAny', ReportTemplate::class);

        return view('admin.reports.publish.templates', ['templates' => ReportTemplate::latest()->paginate(20)]);
    }

    public function templateForm(?ReportTemplate $reportTemplate = null): View
    {
        $reportTemplate ? Gate::authorize('update', $reportTemplate) : Gate::authorize('create', ReportTemplate::class);

        return view('admin.reports.publish.template-form', compact('reportTemplate'));
    }

    public function storeTemplate(SaveReportTemplateRequest $request): RedirectResponse
    {
        $template = ReportTemplate::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('admin.reports.templates.edit', $template)->with('status', 'Template created.');
    }

    public function updateTemplate(SaveReportTemplateRequest $request, ReportTemplate $reportTemplate): RedirectResponse
    {
        Gate::authorize('update', $reportTemplate);
        $reportTemplate->update([...$request->validated(), 'updated_by' => $request->user()->id]);

        return back()->with('status', 'Template updated.');
    }

    public function duplicateTemplate(ReportTemplate $reportTemplate, Request $request): RedirectResponse
    {
        Gate::authorize('create', ReportTemplate::class);
        $copy = $reportTemplate->replicate(['uuid', 'slug', 'is_system', 'created_by', 'updated_by']);
        $copy->fill(['name' => $reportTemplate->name.' Copy', 'slug' => $reportTemplate->slug.'-'.Str::lower(Str::random(6)), 'is_system' => false, 'created_by' => $request->user()->id]);
        $copy->save();

        return redirect()->route('admin.reports.templates.edit', $copy)->with('status', 'Template duplicated.');
    }

    public function toggleTemplate(ReportTemplate $reportTemplate, Request $request): RedirectResponse
    {
        Gate::authorize('update', $reportTemplate);
        $reportTemplate->update(['is_active' => ! $reportTemplate->is_active, 'updated_by' => $request->user()->id]);

        return back()->with('status', $reportTemplate->is_active ? 'Template activated.' : 'Template deactivated.');
    }

    public function schedules(): View
    {
        Gate::authorize('viewAny', ReportSchedule::class);

        return view('admin.reports.publish.schedules', ['schedules' => ReportSchedule::with(['template', 'recipients.user'])->latest()->paginate(20)]);
    }

    public function scheduleForm(?ReportSchedule $reportSchedule = null): View
    {
        $reportSchedule ? Gate::authorize('update', $reportSchedule) : Gate::authorize('create', ReportSchedule::class);

        return view('admin.reports.publish.schedule-form', ['reportSchedule' => $reportSchedule?->load('recipients'), 'templates' => ReportTemplate::where('is_active', true)->get(), 'users' => User::where('is_active', true)->orderBy('name')->get()]);
    }

    public function storeSchedule(SaveReportScheduleRequest $request, ReportScheduleService $service): RedirectResponse
    {
        $schedule = ReportSchedule::create([...$request->safe()->except('recipient_user_ids'), 'created_by' => $request->user()->id]);
        $schedule->update(['next_run_at' => $service->nextRun($schedule)]);
        $this->recipients($schedule, $request);

        return redirect()->route('admin.reports.schedules.edit', $schedule)->with('status', 'Schedule created.');
    }

    public function updateSchedule(SaveReportScheduleRequest $request, ReportSchedule $reportSchedule, ReportScheduleService $service): RedirectResponse
    {
        Gate::authorize('update', $reportSchedule);
        $reportSchedule->update([...$request->safe()->except('recipient_user_ids'), 'updated_by' => $request->user()->id]);
        $reportSchedule->update(['next_run_at' => $reportSchedule->is_active ? $service->nextRun($reportSchedule) : null]);
        $this->recipients($reportSchedule, $request);

        return back()->with('status', 'Schedule updated.');
    }

    public function run(ReportSchedule $reportSchedule, Request $request, ReportScheduleService $service): RedirectResponse
    {
        Gate::authorize('run', $reportSchedule);
        abort_if($reportSchedule->processing_key, 409);
        $filters = $service->filters($reportSchedule);
        $key = (string) Str::uuid();
        $reportSchedule->update(['processing_key' => $key]);
        $snapshot = GeneratedReport::create(['report_template_id' => $reportSchedule->report_template_id, 'report_type' => $reportSchedule->template->report_type, 'title' => $reportSchedule->name, 'reporting_period_start' => $filters['date_from'], 'reporting_period_end' => $filters['date_to'], 'filters' => $filters, 'file_format' => $reportSchedule->output_format, 'generation_status' => 'pending', 'generated_by' => $request->user()->id, 'metadata' => ['schedule_uuid' => $reportSchedule->uuid, 'processing_key' => $key]]);
        GenerateReportJob::dispatch($snapshot->id, $reportSchedule->id);

        return redirect()->route('admin.reports.generated.show', $snapshot)->with('status', 'Schedule queued.');
    }

    private function recipients(ReportSchedule $schedule, Request $request): void
    {
        $schedule->recipients()->delete();
        if ($request->validated('delivery_method') !== 'database_notification') {
            return;
        }
        foreach (array_unique($request->validated('recipient_user_ids', [])) as $id) {
            $schedule->recipients()->create(['user_id' => $id, 'delivery_channel' => 'database_notification', 'configured_by' => $request->user()->id]);
        }
    }
}
