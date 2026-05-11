<?php

namespace App\Models\Concerns;

use App\Models\User;
use Spatie\Permission\Guard;

trait ResolvesGuardUserModel
{
    /**
     * Auth provider model for morphed role/permission → users relations.
     *
     * Spatie passes guard_name to getModelForGuard(); an empty string yields a bad config key and null.
     */
    protected static function authModelClassForPermissionGuard(?string $guardName): string
    {
        $defaultGuard = config('auth.defaults.guard');
        $resolvedGuard = ($guardName !== null && $guardName !== '')
            ? $guardName
            : (is_string($defaultGuard) ? $defaultGuard : 'web');

        $modelClass = Guard::getModelForGuard($resolvedGuard);

        if (is_string($modelClass) && class_exists($modelClass)) {
            return $modelClass;
        }

        $fallback = config('auth.providers.users.model');

        return is_string($fallback) && class_exists($fallback)
            ? $fallback
            : User::class;
    }
}
