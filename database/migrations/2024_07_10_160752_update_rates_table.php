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
        Schema::table('rates', function (Blueprint $table) {
            $table->unsignedBigInteger('related_comment_id')->after('comment_id');
            $table->foreign('related_comment_id')->references('id')->on('comments')->onDelete('cascade');

            //add constraints to comment_id and post_id
            $table->unsignedBigInteger('user_id')->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('post_id')->change();
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');

            $table->unsignedBigInteger('comment_id')->nullable()->change();
            $table->foreign('comment_id')->references('id')->on('comments')->onDelete('cascade');

            //add indices
            $table->index('related_comment_id');
            $table->index('user_id');
            $table->index('post_id');
            $table->index('comment_id');

            //add unique constraint
            $table->unique(['user_id', 'post_id']);
            $table->unique(['user_id', 'comment_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->dropIndex('related_comment_id');
            $table->dropForeign('related_comment_id');
            $table->dropColumn('related_comment_id');

            $table->dropIndex('user_id');
            $table->dropIndex('post_id');
            $table->dropIndex('comment_id');

            $table->dropUnique(['user_id', 'post_id']);
            $table->dropUnique(['user_id', 'comment_id']);

            $table->dropForeign('user_id');
            $table->dropForeign('post_id');
            $table->dropForeign('comment_id');

            $table->integer('user_id')->change();
            $table->integer('post_id')->change();
            $table->integer('comment_id')->default(0)->change();
        });
    }
};
