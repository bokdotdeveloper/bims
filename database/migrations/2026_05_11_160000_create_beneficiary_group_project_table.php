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
        Schema::create('beneficiary_group_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_group_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->date('date_enrolled');
            $table->enum('status', ['Active', 'Completed', 'Dropped', 'Transferred'])
                ->default('Active');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['beneficiary_group_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiary_group_project');
    }
};
