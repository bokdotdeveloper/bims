<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['project_name', 'project_code', 'description', 'date_started', 'date_ended', 'fund_source', 'is_active'])]
#[Table(key: 'id', keyType: 'string', incrementing: false)]
class Project extends Model
{
    use HasUuids, HasFactory;
    protected function casts(): array
    {
        return [
            'date_started' => 'date',
            'date_ended' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function beneficiaries(): BelongsToMany
    {
        return $this->belongsToMany(Beneficiary::class, 'beneficiary_project')
            ->withPivot(['date_enrolled', 'status', 'remarks'])
            ->withTimestamps();
    }
}
