<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('social_media', function (Blueprint $table) {
            // Add the telegram column
            $table->string('telegram', 191)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('social_media', function (Blueprint $table) {
            // Drop the telegram column
            $table->dropColumn('telegram');
        });
    }
};
