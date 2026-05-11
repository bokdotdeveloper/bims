<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['training_tile', 'training_type', 'facilitator', 'venue', 'date_conducted', 'duration_hours', 'project_id'])]
#[Table(key: 'id', keyType: 'string', incrementing: false)]
class Training extends Model
{
    use HasUuids, HasFactory;

    protected function casts(): array
    {
        return [
            'date_conducted' => 'date',
        ];
    }

    public function beneficiaries() : BelongsToMany
    {
        return $this->belongsToMany(Beneficiary::class, 'beneficiary_training')
            ->withPivot(['date_attended', 'completion_status'])
            ->withTimestamps();
    }

    public function project() : BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
