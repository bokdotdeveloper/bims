<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['recipient_type', 'beneficiary_id', 'beneficiary_group_id', 'project_id', 'assistance_type', 'amount', 'date_released', 'released_by', 'remarks'])]
#[Table(key: 'id', keyType: 'string', incrementing: false)]
class AssistanceRecord extends Model
{
    use HasUuids, HasFactory;

    protected function casts(): array
    {
        return [
            'date_released' => 'date',
            'amount' => 'decimal:2'
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function beneficiaryGroup(): BelongsTo
    {
        return $this->belongsTo(BeneficiaryGroup::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
