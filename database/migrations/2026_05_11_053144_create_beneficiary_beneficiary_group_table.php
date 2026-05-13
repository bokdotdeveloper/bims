<?php

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
        Schema::create('beneficiary_beneficiary_group', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_group_id')->constrained()->cascadeOnDelete();
            $table->date('date_joined')->nullable();
            $table->timestamps();

            $table->unique(['beneficiary_id', 'beneficiary_group_id'], 'bbg_ben_id_grp_id_uq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiary_beneficiary_group');
    }
};
