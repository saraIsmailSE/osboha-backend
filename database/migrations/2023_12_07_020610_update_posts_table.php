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
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['is_approved']);

            // Update the timeline_id
            $table->unsignedBigInteger('timeline_id')->change();
            // Add foreign key constraint
            $table->foreign('timeline_id')->references('id')->on('timelines')->onDelete('cascade');

            // Update the type_id
            $table->unsignedBigInteger('type_id')->change();
            // Add foreign key constraint
            $table->foreign('type_id')->references('id')->on('post_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('is_approved');
            $table->dropForeign(['type_id']);
            $table->dropForeign(['timeline_id']);
        });
    }
};
