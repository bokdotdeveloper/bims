<?php

namespace App\Observers;

use App\Models\AssistanceRecord;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class AssistanceRecordObserver
{
    public function created(AssistanceRecord $record): void
    {
        $this->log('created', $record, [], $record->getAttributes());
    }

    public function updated(AssistanceRecord $record): void
    {
        $this->log('updated', $record, $record->getOriginal(), $record->getChanges());
    }

    public function deleted(AssistanceRecord $record): void
    {
        $this->log('deleted', $record, $record->getAttributes(), []);
    }

    private function log(string $action, AssistanceRecord $record, array $old, array $new): void
    {
        AuditLog::create([
            'user_id'        => Auth::id(),
            'beneficiary_id' => $record->beneficiary_id,
            'action'         => $action,
            'model_type'     => AssistanceRecord::class,
            'model_id'       => $record->id,
            'old_values'     => $old ?: null,
            'new_values'     => $new ?: null,
            'ip_address'     => Request::ip(),
        ]);
    }
}
