<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('media');
            // type could be (img - vedio - gif)
            $table->string('type');
            $table->integer('user_id');
            $table->integer('post_id')->nullable();
            $table->integer('comment_id')->nullable();
            $table->integer('reaction_type_id')->nullable();
            $table->integer('infographic_series_id')->nullable();
            $table->integer('infographic_id')->nullable();
            $table->integer('book_id')->nullable();
            $table->integer('group_id')->nullable();
            $table->integer('room_id')->nullable();
            // $table->integer('message_id')->nullable();
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
        Schema::dropIfExists('media');
    }
}
