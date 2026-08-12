<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Make Type Independent
        |--------------------------------------------------------------------------
        |
        | Pehle type ENUM tha:
        |
        | connected
        | not_connected
        | other
        |
        | Ab VARCHAR kar rahe hain taaki koi bhi custom type save ho sake.
        |
        | Existing data safe rahega.
        |
        */

        DB::statement("
            ALTER TABLE call_dispositions
            MODIFY COLUMN type VARCHAR(100) NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Agar custom values save ho chuki hain to ENUM me reverse karne se
        | data truncate ho sakta hai.
        |
        */

        DB::statement("
            UPDATE call_dispositions
            SET type = 'other'
            WHERE type IS NOT NULL
              AND type NOT IN ('connected', 'not_connected', 'other')
        ");

        DB::statement("
            ALTER TABLE call_dispositions
            MODIFY COLUMN type ENUM(
                'connected',
                'not_connected',
                'other'
            ) NULL
        ");
    }
};