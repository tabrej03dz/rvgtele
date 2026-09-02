<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_cities', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Media JSON
            |--------------------------------------------------------------------------
            |
            | Isme city ke saare image/video store honge.
            |
            | Example:
            |
            | [
            |   {
            |      "id": "uuid",
            |      "original_name": "demo.jpg",
            |      "path": "demo-cities/1/5/demo.jpg",
            |      "type": "image",
            |      "mime": "image/jpeg",
            |      "size": 123456
            |   }
            | ]
            |
            */

            $table->json('media')->nullable();

            $table->timestamps();

            $table->unique([
                'company_id',
                'name'
            ]);

            $table->index([
                'company_id',
                'created_at'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_cities');
    }
};