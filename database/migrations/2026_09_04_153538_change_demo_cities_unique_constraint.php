<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_cities', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Remove Old Unique
            |--------------------------------------------------------------------------
            |
            | OLD:
            | company_id + name
            |
            */

            $table->dropUnique(
                'demo_cities_company_id_name_unique'
            );

            /*
            |--------------------------------------------------------------------------
            | New Unique
            |--------------------------------------------------------------------------
            |
            | NEW:
            | company_id + name + category_id
            |
            | Same city different category = allowed
            | Same city same category = not allowed
            |
            */

            $table->unique(
                [
                    'company_id',
                    'name',
                    'category_id',
                ],
                'demo_cities_company_name_category_unique'
            );
        });
    }


    public function down(): void
    {
        Schema::table('demo_cities', function (Blueprint $table) {

            $table->dropUnique(
                'demo_cities_company_name_category_unique'
            );

            $table->unique(
                [
                    'company_id',
                    'name',
                ],
                'demo_cities_company_id_name_unique'
            );
        });
    }
};