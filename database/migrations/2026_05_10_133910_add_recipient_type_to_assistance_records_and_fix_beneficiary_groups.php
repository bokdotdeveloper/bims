<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add columns to beneficiary_groups
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

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->upgradeAssistanceRecordsSqlite();

            return;
        }

        // MySQL, PostgreSQL, etc.: ALTER instead of SQLite table rebuild.
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

        // Other drivers: assume migration not yet applied.
        return true;
    }

    /**
     * SQLite: recreate assistance_records (no ALTER COLUMN support).
     */
    private function upgradeAssistanceRecordsSqlite(): void
    {
        if (! Schema::hasColumn('assistance_records', 'recipient_type')) {
            Schema::table('assistance_records', function (Blueprint $table) {
                $table->string('recipient_type', 20)->default('individual')->after('id');
                $table->unsignedBigInteger('beneficiary_group_id')->nullable()->after('recipient_type');
                $table->foreign('beneficiary_group_id')->references('id')->on('beneficiary_groups')->nullOnDelete();
            });
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('DROP TABLE IF EXISTS assistance_records_new');

        DB::statement('
            CREATE TABLE assistance_records_new (
                id          TEXT NOT NULL PRIMARY KEY,
                recipient_type TEXT NOT NULL DEFAULT \'individual\',
                beneficiary_group_id INTEGER NULL REFERENCES beneficiary_groups(id) ON DELETE SET NULL,
                beneficiary_id TEXT NULL REFERENCES beneficiaries(id) ON DELETE CASCADE,
                project_id  TEXT NULL REFERENCES projects(id) ON DELETE SET NULL,
                assistance_type TEXT NOT NULL,
                amount      NUMERIC NULL,
                date_released TEXT NOT NULL,
                released_by TEXT NULL,
                remarks     TEXT NULL,
                created_at  DATETIME NULL,
                updated_at  DATETIME NULL
            )
        ');

        DB::statement('
            INSERT INTO assistance_records_new
                (id, recipient_type, beneficiary_id, project_id, assistance_type, amount, date_released, released_by, remarks, created_at, updated_at)
            SELECT id, \'individual\', beneficiary_id, project_id, assistance_type, amount, date_released, released_by, remarks, created_at, updated_at
            FROM assistance_records
        ');

        DB::statement('DROP TABLE assistance_records');
        DB::statement('ALTER TABLE assistance_records_new RENAME TO assistance_records');

        DB::statement('CREATE INDEX IF NOT EXISTS assistance_records_beneficiary_id_index ON assistance_records (beneficiary_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS assistance_records_date_released_index ON assistance_records (date_released)');

        DB::statement('PRAGMA foreign_keys = ON');
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
