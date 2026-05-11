<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BeneficiaryObserver
{
    public function created(Beneficiary $beneficiary): void
    {
        $this->log('created', $beneficiary, [], $beneficiary->getAttributes());
    }

    public function updated(Beneficiary $beneficiary): void
    {
        $this->log(
            'updated',
            $beneficiary,
            $beneficiary->getOriginal(),   // values before update
            $beneficiary->getChanges()     // only the fields that changed
        );
    }

    public function deleted(Beneficiary $beneficiary): void
    {
        $this->log('deleted', $beneficiary, $beneficiary->getAttributes(), []);
    }

    public function restored(Beneficiary $beneficiary): void
    {
        $this->log('restored', $beneficiary, [], $beneficiary->getAttributes());
    }

    private function log(string $action, Beneficiary $beneficiary, array $old, array $new): void
    {
        AuditLog::create([
            'user_id'        => Auth::id(),
            'beneficiary_id' => $beneficiary->id,
            'action'         => $action,
            'model_type'     => Beneficiary::class,
            'model_id'       => $beneficiary->id,
            'old_values'     => $old ?: null,
            'new_values'     => $new ?: null,
            'ip_address'     => Request::ip(),
        ]);
    }
}
