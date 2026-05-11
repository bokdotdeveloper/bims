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
        Schema::create('trainings', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('training_title', 200);
            $table->string('training_type', 100);
            $table->string('facilitator', 150)->nullable();
            $table->string('venue', 200)->nullable();
            $table->date('date_conducted');
            $table->decimal('duration_hours', 5, 2)->nullable();
            $table->foreignUuid('project_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
