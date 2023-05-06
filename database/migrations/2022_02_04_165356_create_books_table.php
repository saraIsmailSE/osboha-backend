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
            $table->string('name');
            $table->string('writer');
            $table->string('publisher');
            $table->longText('brief'); //asmaa
            $table->integer('start_page');
            $table->integer('end_page');
            $table->string('link');
            $table->bigInteger('section_id')->unsigned();
            $table->foreign('section_id')->references('id')->on('sections');
            $table->bigInteger('type_id')->unsigned();
            $table->foreign('type_id')->references('id')->on('book_types');
            $table->bigInteger('level_id')->unsigned();
            $table->foreign('level_id')->references('id')->on('book_levels');
            $table->bigInteger('language_id')->unsigned();
            $table->foreign('language_id')->references('id')->on('languages');
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
        Schema::table('tagged_users', function (Blueprint $table) {
            $table->dropForeign(['section_id', 'type_id', 'level_id', 'language_id']);
        });
        Schema::dropIfExists('books');
    }
}