<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->bigInteger('type_id')->unsigned();
            $table->foreign('type_id')->references('id')->on('reaction_types');
            $table->bigInteger('post_id')->nullable()->unsigned();
            $table->foreign('post_id')->references('id')->on('posts');
            $table->bigInteger('comment_id')->nullable()->unsigned();
            $table->foreign('comment_id')->references('id')->on('comments');
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
        Schema::table('reactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['type_id']);
            $table->dropForeign(['post_id']);
            $table->dropForeign(['comment_id']);
        });
        Schema::dropIfExists('reactions');
    }
}