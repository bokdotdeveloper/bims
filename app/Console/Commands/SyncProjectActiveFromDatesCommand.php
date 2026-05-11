<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

class SyncProjectActiveFromDatesCommand extends Command
{
    protected $signature = 'projects:sync-active-from-dates';

    protected $description = 'Update stored is_active on all projects from date_started / date_ended (runs quietly; no model events)';

    public function handle(): int
    {
        $updated = 0;
        Project::query()->chunkById(100, function ($projects) use (&$updated): void {
            foreach ($projects as $project) {
                if ($project->refreshActiveFromDatesQuietly()) {
                    $updated++;
                }
            }
        });

        $this->info("Synced {$updated} project record(s) where is_active changed.");

        return self::SUCCESS;
    }
}
