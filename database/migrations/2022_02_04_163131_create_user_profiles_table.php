<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('first_name_ar');
            $table->string('middle_name_ar');
            $table->string('last_name_ar')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('country')->nullable();
            $table->string('resident')->nullable();
            $table->string('phone')->nullable();
            $table->string('occupation')->nullable();
            $table->string('religion')->nullable();
            $table->string('bio')->nullable();
            $table->string('cover_picture')->nullable();
            $table->string('fav_writer')->nullable();
            $table->string('fav_book')->nullable();
            $table->string('fav_section')->nullable();
            $table->string('fav_quote')->nullable();
            $table->text('extraspace')->nullable();
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
        Schema::dropIfExists('user_profiles');
    }
}
