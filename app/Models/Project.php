<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['project_name', 'project_code', 'description', 'date_started', 'date_ended', 'fund_source'])]
#[Table(key: 'id', keyType: 'string', incrementing: false)]
class Project extends Model
{
    use HasFactory, HasUuids;

    public function setProjectCodeAttribute(?string $value): void
    {
        $this->attributes['project_code'] = $value === null || $value === ''
            ? $value
            : strtoupper(trim($value));
    }

    protected static function booted(): void
    {
        static::saving(function (Project $project): void {
            $project->is_active = $project->deriveActiveFromDates();
        });
    }

    protected function casts(): array
    {
        return [
            'date_started' => 'date',
            'date_ended' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function isScheduled(?CarbonInterface $today = null): bool
    {
        return $this->lifecyclePhase($today) === 'scheduled';
    }

    public function isInProgress(?CarbonInterface $today = null): bool
    {
        return $this->lifecyclePhase($today) === 'active';
    }

    public function isEnded(?CarbonInterface $today = null): bool
    {
        return $this->lifecyclePhase($today) === 'ended';
    }

    /**
     * @return 'scheduled'|'active'|'ended'
     */
    public function lifecyclePhase(?CarbonInterface $today = null): string
    {
        $today = $today instanceof CarbonInterface
            ? Carbon::parse($today)->startOfDay()
            : Carbon::today()->startOfDay();

        $start = $this->date_started?->copy()->startOfDay();
        if ($start === null) {
            return 'ended';
        }
        if ($today->lt($start)) {
            return 'scheduled';
        }

        $end = $this->date_ended?->copy()->startOfDay();
        if ($end !== null && $today->gt($end)) {
            return 'ended';
        }

        return 'active';
    }

    public function deriveActiveFromDates(?CarbonInterface $today = null): bool
    {
        return $this->lifecyclePhase($today) === 'active';
    }

    public function lifecycleStatus(): string
    {
        return match ($this->lifecyclePhase()) {
            'scheduled' => 'Scheduled',
            'active' => 'Active',
            'ended' => 'Ended',
        };
    }

    /** Persisted flag aligned with dates (also refreshed by `projects:sync-active-from-dates`). */
    public function refreshActiveFromDatesQuietly(): bool
    {
        $computed = $this->deriveActiveFromDates();
        if ((bool) $this->is_active === $computed) {
            return false;
        }

        return $this->forceFill(['is_active' => $computed])->saveQuietly();
    }

    /** @param  Builder<Project>  $query */
    public function scopeCurrentlyActiveByDates(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->whereDate('date_started', '<=', $today)
            ->where(function (Builder $q) use ($today): void {
                $q->whereNull('date_ended')
                    ->orWhereDate('date_ended', '>=', $today);
            });
    }

    /** @param  Builder<Project>  $query */
    public function scopeNotCurrentlyActiveByDates(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where(function (Builder $q) use ($today): void {
            $q->whereDate('date_started', '>', $today)
                ->orWhere(function (Builder $q2) use ($today): void {
                    $q2->whereNotNull('date_ended')
                        ->whereDate('date_ended', '<', $today);
                });
        });
    }

    public function beneficiaries(): BelongsToMany
    {
        return $this->belongsToMany(Beneficiary::class, 'beneficiary_project')
            ->withPivot(['date_enrolled', 'status', 'remarks'])
            ->withTimestamps();
    }

    public function beneficiaryGroups(): BelongsToMany
    {
        return $this->belongsToMany(BeneficiaryGroup::class, 'beneficiary_group_project')
            ->withPivot(['date_enrolled', 'status', 'remarks'])
            ->withTimestamps();
    }
}
