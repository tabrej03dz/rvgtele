<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('call_logs', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->constrained('companies')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('call_logs', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('call_logs', 'lead_id')) {
                $table->foreignId('lead_id')
                    ->constrained('leads')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('call_logs', 'mobile')) {
                $table->string('mobile', 30);
            }

            if (!Schema::hasColumn('call_logs', 'direction')) {
                $table->string('direction', 20)->default('outgoing');
            }

            if (!Schema::hasColumn('call_logs', 'started_at')) {
                $table->dateTime('started_at')->nullable();
            }

            if (!Schema::hasColumn('call_logs', 'ended_at')) {
                $table->dateTime('ended_at')->nullable();
            }

            if (!Schema::hasColumn('call_logs', 'duration_seconds')) {
                $table->unsignedInteger('duration_seconds')->default(0);
            }

            if (!Schema::hasColumn('call_logs', 'sim_slot')) {
                $table->unsignedTinyInteger('sim_slot')->nullable();
            }

            if (!Schema::hasColumn('call_logs', 'subscription_id')) {
                $table->unsignedBigInteger('subscription_id')->nullable();
            }

            if (!Schema::hasColumn('call_logs', 'carrier_name')) {
                $table->string('carrier_name', 100)->nullable();
            }

            if (!Schema::hasColumn('call_logs', 'phone_account_id')) {
                $table->string('phone_account_id', 191)->nullable();
            }

            if (!Schema::hasColumn('call_logs', 'remarks')) {
                $table->text('remarks')->nullable();
            }

            if (!Schema::hasColumn('call_logs', 'call_disposition_id')) {
                $table->unsignedBigInteger('call_disposition_id')->nullable();
            }

            if (!Schema::hasColumn('call_logs', 'next_followup_at')) {
                $table->dateTime('next_followup_at')->nullable();
            }

            if (!Schema::hasColumn('call_logs', 'recording_path')) {
                $table->string('recording_path')->nullable();
            }

            if (!Schema::hasColumn('call_logs', 'device_call_key')) {
                $table->string('device_call_key', 191)->nullable();
            }
        });

        Schema::table('call_logs', function (Blueprint $table) {
            $table->unique(
                'device_call_key',
                'call_logs_device_call_key_unique'
            );

            $table->index(
                ['company_id', 'lead_id', 'started_at'],
                'call_logs_company_lead_started_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropUnique('call_logs_device_call_key_unique');
            $table->dropIndex('call_logs_company_lead_started_index');

            $table->dropColumn([
                'mobile',
                'direction',
                'started_at',
                'ended_at',
                'duration_seconds',
                'sim_slot',
                'subscription_id',
                'carrier_name',
                'phone_account_id',
                'next_followup_at',
                'recording_path',
                'device_call_key',
            ]);
        });
    }
};
