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
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('discussion_type', ['public', 'private', 'administrative'])->nullable()->after('status');
            $table->unsignedBigInteger('moved_to_discussion_by')->nullable()->after('discussion_type');

            $table->foreign('moved_to_discussion_by')->references('id')->on('users')->onDelete('cascade')->name('questions_moved_to_discussion_by_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('discussion_type');
            $table->dropForeign('questions_moved_to_discussion_by_foreign');
            $table->dropColumn('moved_to_discussion_by');
        });
    }
};
