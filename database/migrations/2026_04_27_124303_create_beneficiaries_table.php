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
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('beneficiary_code')->unique();
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->date('date_of_birth');
            $table->enum('sex', ['Male', 'Female']);
            $table->string('civil_status', 50);
            $table->text('address');
            $table->string('barangay', 100);
            $table->string('municipality', 100);
            $table->string('province', 100);
            $table->string('contact_number', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['last_name', 'first_name', 'date_of_birth'], 'idx_beneficiary_name_dob');
            $table->index('barangay');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
