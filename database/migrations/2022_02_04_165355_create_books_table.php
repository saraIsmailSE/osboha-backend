<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->integer('post_id')->nullable();
            $table->string('name');
            $table->string('writer');
            $table->string('publisher');
            $table->string('brief');
            $table->integer('start_page');
            $table->integer('end_page');
            $table->string('link');
            $table->string('section');
            $table->string('type');
            $table->string('picture');
            $table->string('level');
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
        Schema::dropIfExists('books');
    }
}
