<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->bigInteger('week_id')->unsigned()->index();
            $table->foreign('week_id')->references('id')->on('weeks');
            $table->integer('reading_mark')->default(0);
            $table->integer('writing_mark')->default(0);
            $table->integer('total_pages')->default(0);
            $table->integer('support')->default(0);
            $table->integer('total_thesis')->default(0);
            $table->integer('total_screenshot')->default(0);
            $table->integer('is_freezed')->default(0);
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
        Schema::table('marks', function (Blueprint $table) {
            $table->dropForeign('user_id');
            $table->dropForeign('week_id');
        });
        Schema::dropIfExists('marks');
    }
}