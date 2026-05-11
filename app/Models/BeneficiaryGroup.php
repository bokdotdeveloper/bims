<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['group_name', 'group_type', 'date_organized'])]
class BeneficiaryGroup extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date_organized' => 'date',
        ];
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Beneficiary::class, 'beneficiary_beneficiary_group')
            ->withPivot(['date_joined'])
            ->withTimestamps();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'beneficiary_group_project')
            ->withPivot(['date_enrolled', 'status', 'remarks'])
            ->withTimestamps();
    }

    /** Persist total / male / female counts from linked beneficiaries (sex field). */
    public function refreshMemberCounts(): void
    {
        $total = $this->members()->count();
        $male = $this->members()->where('beneficiaries.sex', 'Male')->count();
        $female = $this->members()->where('beneficiaries.sex', 'Female')->count();

        $this->forceFill([
            'total_members' => $total,
            'male_members' => $male,
            'female_members' => $female,
        ])->saveQuietly();
    }
}
