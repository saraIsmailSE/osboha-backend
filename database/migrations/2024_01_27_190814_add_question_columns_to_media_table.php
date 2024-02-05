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
        Schema::table('media', function (Blueprint $table) {
            $table->bigInteger("question_id")->unsigned()->nullable();
            $table->foreign("question_id")->references("id")->on("questions")->onDelete("cascade")->name("media_question_id_foreign");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign("media_question_id_foreign");
            $table->dropColumn("question_id");
        });
    }
};
