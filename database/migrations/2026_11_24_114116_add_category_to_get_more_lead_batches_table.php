<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('get_more_lead_batches', function (Blueprint $table) {

            $table->string('state', 120)
                ->nullable()
                ->change();

            $table->string('category', 120)
                ->nullable()
                ->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('get_more_lead_batches', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};