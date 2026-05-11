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
        Schema::create('assistance_records', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique()->comment('Primary key for Assistance Records table');
            $table->foreignUuid('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('assistance_type', 150);
            $table->decimal('amount', 12, 2)->nullable();
            $table->date('date_released');
            $table->string('released_by', 150)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('beneficiary_id');
            $table->index('date_released');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistance_records');
    }
};
