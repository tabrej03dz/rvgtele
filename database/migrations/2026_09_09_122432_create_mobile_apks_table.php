<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_apks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('CRM');
            $table->unsignedInteger('version');
            $table->string('file_path');
            $table->boolean('is_active')->default(true);
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_apks');
    }
};