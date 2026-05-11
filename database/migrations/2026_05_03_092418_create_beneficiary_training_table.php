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
        Schema::create('beneficiary_training', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('training_id')->constrained()->cascadeOnDelete();
            $table->date('date_attended')->nullable();
            $table->enum('completion_status', ['Completed', 'Incomplete', 'Dropped'])
                ->default('Incomplete');
            $table->timestamps();

            $table->unique(['beneficiary_id', 'training_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiary_training');
    }
};
