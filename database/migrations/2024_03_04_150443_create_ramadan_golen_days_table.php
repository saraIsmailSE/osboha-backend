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
        Schema::create('ramadan_golen_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ramadan_day_id');
            $table->integer('sunan_al_rawatib')->default(0);
            $table->integer('tasbeeh')->default(0);
            $table->integer('istighfar')->default(0);
            $table->integer('duha_prayer')->default(0);
            $table->integer('morning_evening_dhikr')->default(0);
            $table->integer('shaf_and_witr')->default(0);
            $table->integer('suhoor')->default(0);
            $table->integer('drink_water')->default(0);
            $table->integer('sleep_amount')->default(0);
            $table->integer('brushing_teeth')->default(0);
            $table->integer('contemplation_of_allahs_signs')->default(0);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ramadan_day_id')->references('id')->on('ramadan_days')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ramadan_golen_days');
    }
};
