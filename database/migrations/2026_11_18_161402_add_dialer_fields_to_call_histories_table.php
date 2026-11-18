
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_histories', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
        });

        Schema::table('call_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id')
                ->nullable()
                ->change();

            $table->string('mobile', 30)->nullable();
            $table->string('direction', 20)->nullable();
            $table->string('call_type', 20)->nullable();
        });

        Schema::table('call_histories', function (Blueprint $table) {
            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('call_histories', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropColumn([
                'mobile',
                'direction',
                'call_type',
            ]);
        });

        Schema::table('call_histories', function (Blueprint $table) {
            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->nullOnDelete();
        });
    }
};
