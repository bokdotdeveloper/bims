<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

final class AuditLogger
{
    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @param  ?string  $beneficiaryContextId  Optional beneficiary to link in the audit row (pivot / enrollment actions).
     */
    public static function record(Model $model, string $action, array $old, array $new, ?string $beneficiaryContextId = null): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'beneficiary_id' => $beneficiaryContextId ?? self::resolveBeneficiaryId($model),
            'action' => $action,
            'model_type' => $model::class,
            'model_id' => self::stringifyKey($model->getKey()),
            'old_values' => $old !== [] ? $old : null,
            'new_values' => $new !== [] ? $new : null,
            'ip_address' => request()?->ip(),
        ]);
    }

    /** Beneficiary linked to a parent model via pivot (group, project enrollment, training participant). */
    public static function recordBeneficiaryMemberAttached(Model $parent, string $beneficiaryId, array $pivotContext = []): void
    {
        self::record(
            $parent,
            'member_added',
            [],
            array_merge(['beneficiary_id' => $beneficiaryId], self::sanitizeAttributes($pivotContext)),
            $beneficiaryId,
        );
    }

    public static function recordBeneficiaryMemberDetached(Model $parent, string $beneficiaryId, array $context = []): void
    {
        self::record(
            $parent,
            'member_removed',
            array_merge(['beneficiary_id' => $beneficiaryId], self::sanitizeAttributes($context)),
            [],
            $beneficiaryId,
        );
    }

    /**
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    public static function recordRolePermissionsChanged(Model $role, array $before, array $after): void
    {
        $before = array_values($before);
        $after = array_values($after);
        sort($before);
        sort($after);
        if ($before === $after) {
            return;
        }
        self::record($role, 'permissions_updated', ['permission_names' => $before], ['permission_names' => $after]);
    }

    /**
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    public static function recordUserRolesChanged(Model $user, array $before, array $after): void
    {
        $before = array_values($before);
        $after = array_values($after);
        sort($before);
        sort($after);
        if ($before === $after) {
            return;
        }
        self::record($user, 'roles_updated', ['role_names' => $before], ['role_names' => $after]);
    }

    /**
     * @return array{0: array, 1: array}
     */
    public static function partitionUpdatedChanges(Model $model): array
    {
        $changes = $model->getChanges();
        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        return [
            self::sanitizeAttributes($old),
            self::sanitizeAttributes($changes),
        ];
    }

    public static function sanitizeAttributes(array $attributes): array
    {
        $out = [];
        foreach ($attributes as $key => $value) {
            if (in_array($key, self::SENSITIVE_KEYS, true)) {
                $out[$key] = $value ? '***redacted***' : null;

                continue;
            }
            $out[$key] = self::normalizeScalar($value);
        }

        return $out;
    }

    private static function normalizeScalar(mixed $value): mixed
    {
        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_array($value)) {
            return array_map(fn ($item) => self::normalizeScalar($item), $value);
        }

        return $value;
    }

    private static function stringifyKey(mixed $key): ?string
    {
        if ($key === null) {
            return null;
        }

        return is_array($key) ? (string) json_encode($key) : (string) $key;
    }

    private static function resolveBeneficiaryId(Model $model): ?string
    {
        if ($model instanceof Beneficiary) {
            return $model->id;
        }

        $beneficiaryId = $model->getAttribute('beneficiary_id');
        if ($beneficiaryId !== null && $beneficiaryId !== '') {
            return (string) $beneficiaryId;
        }

        return null;
    }
}
