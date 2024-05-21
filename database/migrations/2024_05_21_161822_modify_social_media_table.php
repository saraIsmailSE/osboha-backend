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
            // Drop the twitter column
            $table->dropColumn('twitter');

            // Add the whatsapp column
            $table->string('whatsapp', 191)->nullable();
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
            // Add the twitter column back
            $table->string('twitter', 191)->nullable();

            // Drop the whatsapp column
            $table->dropColumn('whatsapp');
        });
    }
};
