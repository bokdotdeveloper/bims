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
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->uuid('beneficiary_id');
            $table->string('relationship', 50);
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 10)->nullable();
            $table->string('civil_status', 50)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('educational_attainment', 100)->nullable();
            $table->boolean('is_pwd')->default(false);
            $table->boolean('is_senior')->default(false);
            $table->uuid('linked_beneficiary_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('beneficiary_id');
            $table->index('linked_beneficiary_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
