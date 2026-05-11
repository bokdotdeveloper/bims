<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['beneficiary_code', 'beneficiary_type', 'last_name', 'first_name', 'middle_name', 'date_of_birth', 'sex', 'civil_status', 'address', 'region', 'barangay', 'municipality', 'province', 'contact_number', 'is_active'])]
#[Table(key: 'id', keyType: 'string', incrementing: false)]
class Beneficiary extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function setBeneficiaryCodeAttribute(?string $value): void
    {
        $this->attributes['beneficiary_code'] = $value === null || $value === ''
            ? $value
            : strtoupper(trim($value));
    }

    protected static function booted(): void
    {
        static::creating(function (Beneficiary $beneficiary): void {
            if (blank($beneficiary->beneficiary_code)) {
                $beneficiary->beneficiary_code = static::generateBeneficiaryCode();
            }
        });
    }

    protected static function generateBeneficiaryCode(): string
    {
        $maxNumber = (int) static::withTrashed()
            ->where('beneficiary_code', 'like', 'BEN-%')
            ->selectRaw('MAX(CAST(SUBSTRING(beneficiary_code, 5) AS UNSIGNED)) as max_number')
            ->value('max_number');

        do {
            $maxNumber++;
            $candidate = sprintf('BEN-%06d', $maxNumber);
        } while (static::withTrashed()->where('beneficiary_code', $candidate)->exists());

        return $candidate;
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'beneficiary_project')
            ->withPivot(['date_enrolled', 'status', 'remarks'])
            ->withTimestamps();
    }

    public function trainings(): BelongsToMany
    {
        return $this->belongsToMany(Training::class, 'beneficiary_training')
            ->withPivot(['date_attended', 'completion_status'])
            ->withTimestamps();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(BeneficiaryGroup::class, 'beneficiary_beneficiary_group')
            ->withPivot(['date_joined'])
            ->withTimestamps();
    }

    public function assistanceRecords(): HasMany
    {
        return $this->hasMany(AssistanceRecord::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }
}
