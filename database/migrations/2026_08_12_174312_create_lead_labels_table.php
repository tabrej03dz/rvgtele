<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_labels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('color', 20)->default('#3B82F6');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'company_id',
                'name',
            ]);
        });

        Schema::create('lead_label_lead', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_label_id')
                ->constrained('lead_labels')
                ->cascadeOnDelete();

            $table->foreignId('lead_id')
                ->constrained('leads')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'lead_label_id',
                'lead_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_label_lead');
        Schema::dropIfExists('lead_labels');
    }
};
