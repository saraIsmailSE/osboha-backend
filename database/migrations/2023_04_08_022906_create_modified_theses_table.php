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
        Schema::create('modified_theses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->bigInteger('thesis_id')->unsigned()->index();
            $table->foreign('thesis_id')->references('id')->on('theses');
            $table->bigInteger('week_id')->unsigned()->index();
            $table->foreign('week_id')->references('id')->on('weeks');
            $table->bigInteger('modifier_id')->unsigned()->index();
            $table->foreign('modifier_id')->references('id')->on('users');
            $table->bigInteger('modifier_reason_id')->unsigned();
            $table->foreign('modifier_reason_id')->references('id')->on('modification_reasons');
            $table->bigInteger('head_modifier_id')->unsigned()->nullable();
            $table->foreign('head_modifier_id')->references('id')->on('users');
            $table->bigInteger('head_modifier_reason_id')->unsigned()->nullable();
            $table->foreign('head_modifier_reason_id')->references('id')->on('modification_reasons');
            $table->enum('status', ['accepted', 'rejected', 'not_audited'])->default('not_audited');
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
        Schema::table('modified_theses', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['thesis_id']);
            $table->dropForeign(['week_id']);
            $table->dropForeign(['modifier_id']);
            $table->dropForeign(['head_modifier_id']);
        });
        Schema::dropIfExists('modified_theses');
    }
};