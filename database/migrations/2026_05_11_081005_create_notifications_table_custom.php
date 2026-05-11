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
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->default('info'); // info | warning | danger
            $table->string('title', 150);
            $table->text('message');
            $table->string('icon', 50)->nullable();       // e.g. 'crossmatch' | 'duplicate'
            $table->json('meta')->nullable();              // extra data (beneficiary ids, etc.)
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
