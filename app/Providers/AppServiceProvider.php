<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\InventoryItem;
use App\Models\Skill;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvaluation;
use App\Models\WorkOrderUpdate;
use App\Policies\AssetMaintenanceRecordPolicy;
use App\Policies\AssetPolicy;
use App\Policies\InventoryItemPolicy;
use App\Policies\MasterDataPolicy;
use App\Policies\NotificationPolicy;
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
        Gate::policy(InventoryItem::class, InventoryItemPolicy::class);
        Gate::policy(WorkOrderEvaluation::class, WorkOrderEvaluationPolicy::class);
        Gate::policy(WorkOrderUpdate::class, WorkOrderUpdatePolicy::class);
        Gate::define('manageMasterData', fn (User $user, string $modelClass): bool => app(MasterDataPolicy::class)->canManage($user, $modelClass));
        Gate::guessPolicyNamesUsing(function (string $modelClass): ?string {
            return is_subclass_of($modelClass, Model::class) ? MasterDataPolicy::class : null;
        });
    }
}
