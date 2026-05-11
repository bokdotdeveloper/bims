<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['group_name', 'group_type', 'total_members', 'male_members', 'female_members', 'date_organized'])]
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
}
