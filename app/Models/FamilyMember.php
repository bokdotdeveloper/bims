<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMember extends Model
{
    protected $fillable = [
        'beneficiary_id',
        'relationship',
        'last_name',
        'first_name',
        'middle_name',
        'date_of_birth',
        'sex',
        'civil_status',
        'occupation',
        'educational_attainment',
        'is_pwd',
        'is_senior',
        'linked_beneficiary_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_pwd'        => 'boolean',
            'is_senior'     => 'boolean',
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function linkedBeneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class, 'linked_beneficiary_id');
    }
}

