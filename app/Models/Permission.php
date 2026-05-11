<?php

namespace App\Models;

use App\Models\Concerns\ResolvesGuardUserModel;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\PermissionRegistrar;

#[Table(key: 'id', keyType: 'string', incrementing: false)]
class Permission extends SpatiePermission
{
    use HasUuids;
    use ResolvesGuardUserModel;

    /**
     * {@inheritdoc}
     */
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            static::authModelClassForPermissionGuard($this->getAttribute('guard_name')),
            'model',
            config('permission.table_names.model_has_permissions'),
            app(PermissionRegistrar::class)->pivotPermission,
            config('permission.column_names.model_morph_key')
        );
    }
}
