<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmbassadorsRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ambassadors_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('members_num');
            $table->enum('ambassadors_gender', ['male', 'female', 'any']);
            $table->enum('leader_gender', ['male', 'female']);
            $table->unsignedBigInteger('applicant_id');
            $table->foreign('applicant_id')->references('id')->on('users');
            $table->integer('group_id');
            $table->integer('high_priority')->default(0);
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
        Schema::dropIfExists('ambassadors_requests');
    }
}
