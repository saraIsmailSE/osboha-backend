<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('book_statistics', function (Blueprint $table) {
            $table->id();
            $table->integer('total');
            $table->integer('simple');
            $table->integer('intermediate');
            $table->integer('advanced');
            $table->integer('method_books');
            $table->integer('ramadan_books');
            $table->integer('children_books');
            $table->integer('young_people_books');
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
        Schema::dropIfExists('book_statistics');
    }
}
