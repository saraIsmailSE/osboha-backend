<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->text('body');
            $table->integer('user_id');
            $table->integer('timeline_id');
            $table->integer('type_id');
            $table->boolean('allow_comments')->default(1);
            $table->text('tag')->nullable();
            $table->text('vote')->nullable();
            $table->timestamp('is_approved')->nullable()->useCurrent();
            $table->boolean('is_pinned')->default(0);
            $table->integer('book_id')->nullable();
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
        Schema::dropIfExists('posts');
    }
}
