<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calling_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('sim_slot');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('carrier_name', 100)->nullable();
            $table->string('phone_account_id', 191)->nullable();

            $table->timestamps();

            $table->unique('user_id');
            $table->index(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calling_settings');
    }
};
