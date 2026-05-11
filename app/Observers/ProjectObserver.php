<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectObserver
{
    public function created(Project $project): void
    {
        $this->log('created', $project, [], $project->getAttributes());
    }

    public function updated(Project $project): void
    {
        $this->log('updated', $project, $project->getOriginal(), $project->getChanges());
    }

    public function deleted(Project $project): void
    {
        $this->log('deleted', $project, $project->getAttributes(), []);
    }

    private function log(string $action, Project $project, array $old, array $new): void
    {
        AuditLog::create([
            'user_id'        => Auth::id(),
            'beneficiary_id' => null,
            'action'         => $action,
            'model_type'     => Project::class,
            'model_id'       => $project->id,
            'old_values'     => $old ?: null,
            'new_values'     => $new ?: null,
            'ip_address'     => Request::ip(),
        ]);
    }
}
