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
        Schema::create('ramadan_hadiths', function (Blueprint $table) {
            $table->id();
            $table->string('hadith_title');
            $table->text('hadith');
            $table->unsignedBigInteger('ramadan_day_id');
            $table->timestamps();

            $table->foreign('ramadan_day_id')->references('id')->on('ramadan_days');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ramadan_hadiths');
    }
};
