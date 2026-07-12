<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\GeneratedReport;
use App\Models\InventoryItem;
use App\Models\KpiCorrectiveAction;
use App\Models\KpiDefinition;
use App\Models\KpiEvaluation;
use App\Models\KpiTarget;
use App\Models\MaintenanceSchedule;
use App\Models\ReportDeliveryLog;
use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use App\Models\Skill;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvaluation;
use App\Models\WorkOrderUpdate;
use App\Observers\KpiAssignmentObserver;
use App\Observers\ReportAuditObserver;
use App\Policies\AssetMaintenanceRecordPolicy;
use App\Policies\AssetPolicy;
use App\Policies\GeneratedReportPolicy;
use App\Policies\InventoryItemPolicy;
use App\Policies\KpiCorrectiveActionPolicy;
use App\Policies\KpiDefinitionPolicy;
use App\Policies\KpiEvaluationPolicy;
use App\Policies\KpiTargetPolicy;
use App\Policies\MaintenanceSchedulePolicy;
use App\Policies\MasterDataPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ReportDeliveryLogPolicy;
use App\Policies\ReportPolicy;
use App\Policies\ReportSchedulePolicy;
use App\Policies\ReportTemplatePolicy;
use App\Policies\SkillPolicy;
use App\Policies\StaffProfilePolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkOrderEvaluationPolicy;
use App\Policies\WorkOrderPolicy;
use App\Policies\WorkOrderUpdatePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(DatabaseNotification::class, NotificationPolicy::class);
        Gate::policy(StaffProfile::class, StaffProfilePolicy::class);
        Gate::policy(Skill::class, SkillPolicy::class);
        Gate::policy(WorkOrder::class, WorkOrderPolicy::class);
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(AssetMaintenanceRecord::class, AssetMaintenanceRecordPolicy::class);
        Gate::policy(MaintenanceSchedule::class, MaintenanceSchedulePolicy::class);
        Gate::policy(InventoryItem::class, InventoryItemPolicy::class);
        Gate::policy(KpiDefinition::class, KpiDefinitionPolicy::class);
        Gate::policy(KpiTarget::class, KpiTargetPolicy::class);
        Gate::policy(KpiEvaluation::class, KpiEvaluationPolicy::class);
        Gate::policy(KpiCorrectiveAction::class, KpiCorrectiveActionPolicy::class);
        KpiDefinition::observe(ReportAuditObserver::class);
        KpiTarget::observe(ReportAuditObserver::class);
        KpiCorrectiveAction::observe(ReportAuditObserver::class);
        KpiTarget::observe(KpiAssignmentObserver::class);
        KpiCorrectiveAction::observe(KpiAssignmentObserver::class);
        Gate::policy(ReportTemplate::class, ReportTemplatePolicy::class);
        Gate::policy(GeneratedReport::class, GeneratedReportPolicy::class);
        Gate::policy(ReportSchedule::class, ReportSchedulePolicy::class);
        Gate::policy(ReportDeliveryLog::class, ReportDeliveryLogPolicy::class);
        ReportTemplate::observe(ReportAuditObserver::class);
        GeneratedReport::observe(ReportAuditObserver::class);
        ReportSchedule::observe(ReportAuditObserver::class);
        Gate::policy(WorkOrderEvaluation::class, WorkOrderEvaluationPolicy::class);
        Gate::policy(WorkOrderUpdate::class, WorkOrderUpdatePolicy::class);
        Gate::define('manageMasterData', fn (User $user, string $modelClass): bool => app(MasterDataPolicy::class)->canManage($user, $modelClass));
        Gate::define('viewReports', fn (User $user): bool => app(ReportPolicy::class)->viewAny($user));
        Gate::define('viewWorkOrderReports', fn (User $user): bool => app(ReportPolicy::class)->workOrders($user));
        Gate::define('viewAssetReports', fn (User $user): bool => app(ReportPolicy::class)->assets($user));
        Gate::define('viewMaintenanceReports', fn (User $user): bool => app(ReportPolicy::class)->maintenance($user));
        Gate::define('viewInventoryReports', fn (User $user): bool => app(ReportPolicy::class)->inventory($user));
        Gate::define('viewStaffReports', fn (User $user): bool => app(ReportPolicy::class)->staff($user));
        Gate::define('exportReports', fn (User $user): bool => app(ReportPolicy::class)->export($user));
        Gate::guessPolicyNamesUsing(function (string $modelClass): ?string {
            return is_subclass_of($modelClass, Model::class) ? MasterDataPolicy::class : null;
        });
    }
}
