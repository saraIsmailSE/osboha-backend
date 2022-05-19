<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaderRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leader_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('members_num');
            //gender male - female - any
            $table->string('gender');
            $table->integer('leader_id');
            $table->integer('current_team_count');
            $table->boolean('is_done')->default(0);
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
        Schema::dropIfExists('leader_requests');
    }
}
