<?php

// Portable schema only (MySQL/MariaDB/PostgreSQL/SQLite). No PRAGMA — deploy this file to prod or migrations will keep failing on MySQL.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('beneficiary_groups', 'group_name')) {
            Schema::table('beneficiary_groups', function (Blueprint $table) {
                $table->string('group_name', 255)->default('');
                $table->string('group_type', 100)->nullable();
                $table->unsignedInteger('total_members')->nullable();
                $table->unsignedInteger('male_members')->nullable();
                $table->unsignedInteger('female_members')->nullable();
                $table->date('date_organized')->nullable();
            });
        }

        if (! Schema::hasColumn('assistance_records', 'recipient_type')) {
            Schema::table('assistance_records', function (Blueprint $table) {
                $table->string('recipient_type', 20)->default('individual')->after('id');
                $table->unsignedBigInteger('beneficiary_group_id')->nullable()->after('recipient_type');
                $table->foreign('beneficiary_group_id')->references('id')->on('beneficiary_groups')->nullOnDelete();
            });
        }

        if ($this->assistanceBeneficiaryIdMustBecomeNullable()) {
            Schema::table('assistance_records', function (Blueprint $table) {
                $table->dropForeign(['beneficiary_id']);
            });

            Schema::table('assistance_records', function (Blueprint $table) {
                $table->foreignUuid('beneficiary_id')->nullable()->change();
            });

            Schema::table('assistance_records', function (Blueprint $table) {
                $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
            });
        }
    }

    /**
     * True when beneficiary_id is still NOT NULL (e.g. after a failed run that stopped before this step).
     */
    private function assistanceBeneficiaryIdMustBecomeNullable(): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $row = $connection->selectOne(
                'SELECT IS_NULLABLE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['assistance_records', 'beneficiary_id']
            );

            return ! $row || $row->IS_NULLABLE === 'NO';
        }

        if ($driver === 'pgsql') {
            $row = $connection->selectOne(
                'SELECT is_nullable FROM information_schema.columns
                 WHERE table_schema = ANY (current_schemas(false)) AND table_name = ? AND column_name = ?',
                ['assistance_records', 'beneficiary_id']
            );

            return ! $row || $row->is_nullable === 'NO';
        }

        if ($driver === 'sqlite') {
            $row = $connection->selectOne(
                'SELECT "notnull" AS is_required FROM pragma_table_info(\'assistance_records\') WHERE name = \'beneficiary_id\''
            );

            return ! $row || (int) $row->is_required === 1;
        }

        return true;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistance_records', function (Blueprint $table) {
            $table->dropForeign(['beneficiary_group_id']);
            $table->dropForeign(['beneficiary_id']);
        });

        Schema::table('assistance_records', function (Blueprint $table) {
            $table->dropColumn(['recipient_type', 'beneficiary_group_id']);
        });

        Schema::table('assistance_records', function (Blueprint $table) {
            $table->foreignUuid('beneficiary_id')->nullable(false)->change();
        });

        Schema::table('assistance_records', function (Blueprint $table) {
            $table->foreign('beneficiary_id')->references('id')->on('beneficiaries')->cascadeOnDelete();
        });

        Schema::table('beneficiary_groups', function (Blueprint $table) {
            $table->dropColumn(['group_name', 'group_type', 'total_members', 'male_members', 'female_members', 'date_organized']);
        });
    }
};
