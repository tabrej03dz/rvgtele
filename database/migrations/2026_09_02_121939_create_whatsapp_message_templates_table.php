<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_message_templates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name', 150);

            $table->text('message');

            /*
            |--------------------------------------------------------------------------
            | Global Template
            |--------------------------------------------------------------------------
            |
            | false = user ka personal template
            | true  = company/admin ka common template
            |
            */

            $table->boolean('is_global')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index([
                'company_id',
                'user_id',
                'is_global',
                'is_active',
            ], 'wa_templates_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_templates');
    }
};
