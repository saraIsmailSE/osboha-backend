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
            $table->integer('total')->default(0);
            $table->integer('simple')->default(0);
            $table->integer('intermediate')->default(0);
            $table->integer('advanced')->default(0);
            $table->integer('method_books')->default(0);
            $table->integer('ramadan_books')->default(0);
            $table->integer('children_books')->default(0);
            $table->integer('young_people_books')->default(0);
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
