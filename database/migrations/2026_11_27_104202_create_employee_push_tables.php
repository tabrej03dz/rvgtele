<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_push_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message');
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_push_recipients', function (Blueprint $table) {
            $table->foreignId('notification_id')
                ->constrained('employee_push_notifications', 'id', 'employee_push_notification_fk')
                ->cascadeOnDelete();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['notification_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_push_recipients');
        Schema::dropIfExists('employee_push_notifications');
    }
};