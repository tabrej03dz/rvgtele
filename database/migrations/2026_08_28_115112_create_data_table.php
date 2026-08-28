<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Basic Details
            |--------------------------------------------------------------------------
            */
            $table->string('name')->nullable();
            $table->string('company_name')->nullable();

            $table->string('mobile', 20)->nullable();
            $table->string('alternate_mobile', 20)->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('email')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Category / Source
            |--------------------------------------------------------------------------
            */
            $table->string('category')->nullable();
            $table->string('lead_source')->nullable();
            $table->string('campaign')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Location
            |--------------------------------------------------------------------------
            */
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Business Details
            |--------------------------------------------------------------------------
            */
            $table->string('industry')->nullable();
            $table->string('required_product')->nullable();
            $table->string('preferred_language')->nullable();

            $table->decimal('estimated_budget', 14, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */
            $table->text('remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Lead Conversion
            |--------------------------------------------------------------------------
            */
            $table->boolean('converted')->default(false);

            $table->foreignId('lead_id')
                ->nullable()
                ->constrained('leads')
                ->nullOnDelete();

            $table->dateTime('converted_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Raw Imported Data
            |--------------------------------------------------------------------------
            */
            $table->json('raw_data')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */
            $table->index(['company_id', 'mobile']);
            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'converted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data');
    }
};