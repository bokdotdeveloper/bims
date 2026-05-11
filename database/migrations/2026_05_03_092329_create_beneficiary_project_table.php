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
        Schema::create('beneficiary_project', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('beneficiary_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
                $table->date('date_enrolled');
                $table->enum('status', ['Active', 'Completed', 'Dropped', 'Transferred'])
                    ->default('Active');
                $table->text('remarks')->nullable();
                $table->timestamps();

                // Prevent duplicate enrollment in the same project
                $table->unique(['beneficiary_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiary_project');
    }
};
