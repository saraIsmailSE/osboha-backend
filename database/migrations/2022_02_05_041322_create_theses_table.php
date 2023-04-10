<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThesesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('theses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('comment_id')->unsigned()->index();
            $table->foreign('comment_id')->references('id')->on('comments');
            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->integer('max_length')->default(0);
            $table->bigInteger('book_id')->unsigned()->index();
            $table->foreign('book_id')->references('id')->on('books');
            $table->bigInteger('mark_id')->unsigned()->index();
            $table->foreign('mark_id')->references('id')->on('marks');
            $table->bigInteger('type_id')->unsigned()->index();
            $table->foreign('type_id')->references('id')->on('thesis_types');
            $table->integer('start_page');
            $table->integer('end_page');
            $table->integer('total_screenshots')->default(0);
            $table->enum('status', ['pending', 'accepted', 'rejected', 'one_thesis'])->default('pending');
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
        Schema::table('theses', function (Blueprint $table) {
            $table->dropForeign('comment_id');
            $table->dropForeign('user_id');
            $table->dropForeign('book_id');
            $table->dropForeign('mark_id');
            $table->dropForeign('type_id');
        });
        Schema::dropIfExists('theses');
    }
}