<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Older installs used an unnamed UUID column (defaults to "uuid"); models expect "id".
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissionsTable = config('permission.table_names.permissions');
        $rolesTable = config('permission.table_names.roles');

        if (! is_string($permissionsTable) || ! is_string($rolesTable)) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            if (Schema::hasTable($permissionsTable) && Schema::hasColumn($permissionsTable, 'uuid') && ! Schema::hasColumn($permissionsTable, 'id')) {
                Schema::table($permissionsTable, function (Blueprint $table) {
                    $table->renameColumn('uuid', 'id');
                });
            }

            if (Schema::hasTable($rolesTable) && Schema::hasColumn($rolesTable, 'uuid') && ! Schema::hasColumn($rolesTable, 'id')) {
                Schema::table($rolesTable, function (Blueprint $table) {
                    $table->renameColumn('uuid', 'id');
                });
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
