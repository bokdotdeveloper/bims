<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repair pivot tables created by an earlier migration that omitted FK columns.
     */
    public function up(): void
    {
        if (! Schema::hasTable('beneficiary_beneficiary_group')) {
            return;
        }

        if (Schema::hasColumn('beneficiary_beneficiary_group', 'beneficiary_id')) {
            return;
        }

        Schema::table('beneficiary_beneficiary_group', function (Blueprint $table) {
            $table->foreignUuid('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_group_id')->constrained()->cascadeOnDelete();
            $table->date('date_joined')->nullable();
            $table->unique(['beneficiary_id', 'beneficiary_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('beneficiary_beneficiary_group')) {
            return;
        }

        if (! Schema::hasColumn('beneficiary_beneficiary_group', 'beneficiary_id')) {
            return;
        }

        Schema::table('beneficiary_beneficiary_group', function (Blueprint $table) {
            $table->dropForeign(['beneficiary_id']);
            $table->dropForeign(['beneficiary_group_id']);
            $table->dropUnique(['beneficiary_id', 'beneficiary_group_id']);
            $table->dropColumn(['beneficiary_id', 'beneficiary_group_id', 'date_joined']);
        });
    }
};
