<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_exceptions', function (Blueprint $table) {
            DB::statement("ALTER TABLE user_exceptions MODIFY desired_duration ENUM('مؤقت', 'أسبوع واحد', 'أسبوعين', 'ثلاثة أسابيع')");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_exceptions', function (Blueprint $table) {
            DB::statement("ALTER TABLE user_exceptions MODIFY desired_duration ENUM('أسبوع واحد', 'أسبوعين', 'ثلاثة أسابيع')");
        });
    }
};
