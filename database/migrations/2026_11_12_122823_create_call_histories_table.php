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
        Schema::create('call_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
    
            $table->string('call_status', 255)->nullable();

            $table->dateTime('call_at')->nullable();

            $table->string('recording', 255)->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unsignedInteger('call_duration')->nullable();
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Index for faster lookup
            $table->index('lead_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_histories');
    }
};
