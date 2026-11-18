<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('distribution_phone', 20)->nullable()->index();
            $table->boolean('distribution_indexed')->default(false)->index();
        });

        Schema::create('distribution_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->string('state', 120);
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_allocated_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        Schema::create('distribution_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('phone', 20);
            $table->unsignedBigInteger('lead_id');
            $table->string('status', 30)->default('available');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'phone']);
            $table->unique('lead_id');
            $table->index(['company_id', 'status', 'available_at']);
        });

        Schema::create('distribution_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('number_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('first_called_at')->nullable();

            $table->unique(['number_id', 'user_id']);
        });

        Schema::create('distribution_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->string('state', 120);
            $table->string('status', 20)->default('active');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id', 'status']);
        });

        Schema::create('distribution_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('number_id');
            $table->unsignedBigInteger('lead_id');
            $table->string('city', 120);
            $table->string('outcome', 30)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'number_id']);
            $table->index(['batch_id', 'completed_at']);
        });

        Schema::table('call_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('distribution_item_id')
                ->nullable()
                ->index();

            $table->uuid('client_call_id')->nullable();

            $table->unique(
                ['user_id', 'client_call_id'],
                'call_history_client_call_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('call_histories', function (Blueprint $table) {
            $table->dropUnique('call_history_client_call_unique');
            $table->dropColumn([
                'distribution_item_id',
                'client_call_id',
            ]);
        });

        Schema::dropIfExists('distribution_items');
        Schema::dropIfExists('distribution_batches');
        Schema::dropIfExists('distribution_visits');
        Schema::dropIfExists('distribution_numbers');
        Schema::dropIfExists('distribution_preferences');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'distribution_phone',
                'distribution_indexed',
            ]);
        });
    }
};