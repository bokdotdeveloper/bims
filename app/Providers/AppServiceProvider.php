<?php

namespace App\Providers;

use App\Models\AppNotification;
use App\Models\AssistanceRecord;
use App\Models\Beneficiary;
use App\Models\BeneficiaryGroup;
use App\Models\FamilyMember;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Training;
use App\Models\User;
use App\Observers\AuditableObserver;
use Illuminate\Database\Eloquent\Model;
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
        /** @var list<class-string<Model>> $auditable */
        $auditable = [
            Beneficiary::class,
            AssistanceRecord::class,
            Project::class,
            Training::class,
            FamilyMember::class,
            BeneficiaryGroup::class,
            User::class,
            Role::class,
            Permission::class,
            AppNotification::class,
        ];

        foreach ($auditable as $modelClass) {
            $modelClass::observe(AuditableObserver::class);
        }
    }
}
