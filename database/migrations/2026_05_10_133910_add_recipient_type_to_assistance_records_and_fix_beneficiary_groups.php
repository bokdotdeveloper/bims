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
        Schema::table('beneficiary_groups', function (Blueprint $table) {
            $table->string('group_name', 255)->default('');
            $table->string('group_type', 100)->nullable();
            $table->unsignedInteger('total_members')->nullable();
            $table->unsignedInteger('male_members')->nullable();
            $table->unsignedInteger('female_members')->nullable();
            $table->date('date_organized')->nullable();
        });

        // 2. Add recipient_type and beneficiary_group_id to assistance_records
        Schema::table('assistance_records', function (Blueprint $table) {
            $table->string('recipient_type', 20)->default('individual')->after('id');
            $table->unsignedBigInteger('beneficiary_group_id')->nullable()->after('recipient_type');
            $table->foreign('beneficiary_group_id')->references('id')->on('beneficiary_groups')->nullOnDelete();
        });

        // 3. For SQLite: recreate assistance_records to make beneficiary_id nullable
        // SQLite doesn't support ALTER COLUMN, so we use a table rebuild approach.
        DB::statement('PRAGMA foreign_keys = OFF');

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
            $table->dropColumn(['recipient_type', 'beneficiary_group_id']);
        });

        Schema::table('beneficiary_groups', function (Blueprint $table) {
            $table->dropColumn(['group_name', 'group_type', 'total_members', 'male_members', 'female_members', 'date_organized']);
        });
    }
};
