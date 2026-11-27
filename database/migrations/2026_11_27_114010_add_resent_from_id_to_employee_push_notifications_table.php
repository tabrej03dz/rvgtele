<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_push_notifications', function (Blueprint $table) {
            $table->foreignId('resent_from_id')
                ->nullable()
                ->constrained('employee_push_notifications')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_push_notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resent_from_id');
        });
    }
};