<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('get_more_lead_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->string('state', 120);

            // इस ID तक की call histories assignment से पहले की हैं।
            $table->unsignedBigInteger('history_start_id')->default(0);

            // Batch पूरा होने पर उसकी history की आखिरी सीमा save होगी।
            $table->unsignedBigInteger('history_end_id')->nullable();

            $table->timestamps();

            $table->index(
                ['company_id', 'user_id', 'state', 'id'],
                'gml_user_state_idx'
            );
        });

        Schema::create('get_more_lead_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('batch_id')
                ->constrained('get_more_lead_batches')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('lead_id');

            // Assignment के समय की city।
            $table->string('city');

            $table->unique(['batch_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('get_more_lead_items');
        Schema::dropIfExists('get_more_lead_batches');
    }
};