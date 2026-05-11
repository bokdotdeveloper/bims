<?php

namespace App\Providers;

use App\Models\AssistanceRecord;
use App\Models\Beneficiary;
use App\Models\Project;
use App\Observers\AssistanceRecordObserver;
use App\Observers\BeneficiaryObserver;
use App\Observers\ProjectObserver;
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
        Beneficiary::observe(BeneficiaryObserver::class);
        AssistanceRecord::observe(AssistanceRecordObserver::class);
        Project::observe(ProjectObserver::class);
    }
}
