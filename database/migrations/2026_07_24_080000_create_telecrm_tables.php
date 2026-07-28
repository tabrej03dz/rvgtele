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
        /*
        |--------------------------------------------------------------------------
        | Companies
        |--------------------------------------------------------------------------
        */

        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code')->unique();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        /*
        |--------------------------------------------------------------------------
        | Branches
        |--------------------------------------------------------------------------
        */

        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code');
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique([
                'company_id',
                'code',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Teams
        |--------------------------------------------------------------------------
        */

        Schema::create('teams', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');

            $table->foreignId('leader_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Add CRM Fields to Users
        |--------------------------------------------------------------------------
        */

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->after('company_id')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('team_id')
                ->nullable()
                ->after('branch_id')
                ->constrained()
                ->nullOnDelete();

            $table->string('phone', 20)
                ->nullable()
                ->after('email');

            $table->string('employee_code')
                ->nullable()
                ->unique();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamp('last_active_at')
                ->nullable();
        });

        /*
        |--------------------------------------------------------------------------
        | Lead Sources
        |--------------------------------------------------------------------------
        */

        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Lead Statuses
        |--------------------------------------------------------------------------
        */

        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->string('color')->default('#64748b');

            $table->unsignedInteger('sort_order')->default(0);

            $table->boolean('is_converted')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Call Dispositions
        |--------------------------------------------------------------------------
        */

        Schema::create('call_dispositions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->enum('type', [
                'connected',
                'not_connected',
                'other',
            ])->default('connected');

            $table->boolean('requires_follow_up')->default(false);
            $table->boolean('requires_remarks')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Pipelines
        |--------------------------------------------------------------------------
        */

        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Pipeline Stages
        |--------------------------------------------------------------------------
        */

        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pipeline_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('color')->default('#64748b');

            $table->unsignedTinyInteger('probability')->default(0);
            $table->unsignedInteger('sort_order')->default(0);

            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Campaigns
        |--------------------------------------------------------------------------
        */

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->unsignedInteger('target_calls')->default(0);
            $table->unsignedInteger('target_conversions')->default(0);

            $table->decimal('budget', 14, 2)->default(0);

            $table->enum('status', [
                'draft',
                'active',
                'paused',
                'completed',
            ])->default('draft');

            $table->text('description')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code');

            $table->text('description')->nullable();

            $table->decimal('base_price', 14, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique([
                'company_id',
                'code',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Leads
        |--------------------------------------------------------------------------
        */

        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('team_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('lead_source_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('lead_status_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('campaign_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('pipeline_stage_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');
            $table->string('company_name')->nullable();

            $table->string('mobile', 20);
            $table->string('alternate_mobile', 20)->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('email')->nullable();

            $table->string('preferred_language')->nullable();

            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();

            $table->string('industry')->nullable();
            $table->string('required_product')->nullable();

            $table->decimal('estimated_budget', 14, 2)->nullable();

            $table->enum('priority', [
                'low',
                'normal',
                'high',
                'urgent',
                'hot',
            ])->default('normal');

            $table->enum('temperature', [
                'cold',
                'warm',
                'hot',
            ])->default('cold');

            $table->unsignedInteger('score')->default(0);

            $table->dateTime('next_follow_up_at')->nullable();
            $table->date('expected_closing_date')->nullable();

            $table->decimal('expected_deal_value', 14, 2)->nullable();

            $table->dateTime('last_contact_at')->nullable();

            $table->boolean('do_not_call')->default(false);

            $table->json('custom_data')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'company_id',
                'mobile',
            ]);

            $table->index([
                'assigned_to',
                'next_follow_up_at',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Lead Assignments
        |--------------------------------------------------------------------------
        */

        Schema::create('lead_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('previous_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('new_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('reason')->nullable();

            $table->timestamp('assigned_at');

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Call Logs
        |--------------------------------------------------------------------------
        */

        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('lead_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('call_disposition_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('direction', [
                'outgoing',
                'incoming',
            ])->default('outgoing');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->unsignedInteger('duration_seconds')->default(0);

            $table->text('remarks')->nullable();
            $table->string('recording_url')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Follow Ups
        |--------------------------------------------------------------------------
        */

        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('lead_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('assigned_to')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('type', [
                'phone',
                'whatsapp',
                'sms',
                'email',
                'meeting',
                'visit',
                'demo',
                'payment',
                'document',
            ])->default('phone');

            $table->dateTime('scheduled_at');

            $table->enum('priority', [
                'low',
                'normal',
                'high',
                'urgent',
            ])->default('normal');

            $table->enum('status', [
                'pending',
                'completed',
                'rescheduled',
                'cancelled',
                'missed',
            ])->default('pending');

            $table->text('notes')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Tasks
        |--------------------------------------------------------------------------
        */

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('lead_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('assigned_to')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->dateTime('due_at')->nullable();

            $table->enum('priority', [
                'low',
                'normal',
                'high',
                'urgent',
            ])->default('normal');

            $table->enum('status', [
                'pending',
                'in_progress',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->text('completion_notes')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Notes
        |--------------------------------------------------------------------------
        */

        Schema::create('notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->text('body');

            $table->boolean('is_private')->default(false);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */

        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('lead_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('account_manager_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name');
            $table->string('company_name')->nullable();

            $table->string('mobile', 20);
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            $table->enum('category', [
                'new',
                'active',
                'repeat',
                'premium',
                'inactive',
                'blacklisted',
            ])->default('new');

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Quotations
        |--------------------------------------------------------------------------
        */

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('lead_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('quotation_number')->unique();

            $table->date('quotation_date');
            $table->date('valid_until')->nullable();

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->enum('status', [
                'draft',
                'sent',
                'viewed',
                'accepted',
                'rejected',
                'expired',
                'converted',
            ])->default('draft');

            $table->text('notes')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Quotation Items
        |--------------------------------------------------------------------------
        */

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quotation_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('description');

            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('rate', 14, 2);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('total', 14, 2);

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Orders
        |--------------------------------------------------------------------------
        */

        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('lead_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('order_number')->unique();

            $table->date('order_date');

            $table->decimal('total_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);

            $table->enum('payment_status', [
                'unpaid',
                'partial',
                'paid',
                'overdue',
                'refunded',
            ])->default('unpaid');

            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'completed',
                'cancelled',
                'refunded',
            ])->default('pending');

            $table->text('notes')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Payments
        |--------------------------------------------------------------------------
        */

        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->decimal('amount', 14, 2);

            $table->date('payment_date');

            $table->enum('method', [
                'cash',
                'upi',
                'card',
                'bank_transfer',
                'cheque',
                'gateway',
                'other',
            ])->default('cash');

            $table->string('transaction_reference')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Activity Logs
        |--------------------------------------------------------------------------
        */

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('event');

            $table->nullableMorphs('subject');

            $table->json('properties')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('call_logs');
        Schema::dropIfExists('lead_assignments');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('products');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');
        Schema::dropIfExists('call_dispositions');
        Schema::dropIfExists('lead_statuses');
        Schema::dropIfExists('lead_sources');

        /*
        |--------------------------------------------------------------------------
        | Remove User Foreign Keys Before Dropping Teams
        |--------------------------------------------------------------------------
        */

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('company_id');

            $table->dropColumn([
                'phone',
                'employee_code',
                'is_active',
                'last_active_at',
            ]);
        });

        Schema::dropIfExists('teams');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};

