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
        Schema::table('poll_votes', function (Blueprint $table) {
            $table->index(['user_id']);
            $table->index(['post_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('poll_votes', function (Blueprint $table) {
            $table->dropIndex('user_id');
            $table->dropIndex('post_id');
        });
    }
};
