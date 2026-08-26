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
        Schema::table('follow_ups', function (Blueprint $table) {
              $table->timestamp('reminder_notified_at')
                ->nullable()
                ->after('scheduled_at');

            $table->index([
                'status',
                'scheduled_at',
                'reminder_notified_at',
            ], 'followups_reminder_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropIndex(
                'followups_reminder_lookup_index'
            );

            $table->dropColumn('reminder_notified_at');
        });
    }
};
