<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfileSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profile_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            // 1- for public 2- for friends 3- only me
            $table->integer('posts')->default(1);
            $table->integer('media')->default(1);
            $table->integer('certificates')->default(1);
            $table->integer('infographics')->default(1);
            $table->integer('articles')->default(1);
            $table->integer('thesis')->default(1);
            $table->integer('books')->default(1);
            $table->integer('marks')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profile_settings');
    }
}
