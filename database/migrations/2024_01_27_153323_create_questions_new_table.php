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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->text("question");
            $table->enum("status", ["open", "discussion", "solved"])->default("open");
            $table->bigInteger("user_id")->unsigned();
            $table->bigInteger("current_assignee_id")->unsigned()->nullable();
            $table->dateTime("closed_at")->nullable();

            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade")->name("questions_user_id_foreign");
            $table->foreign("current_assignee_id")->references("id")->on("users")->onDelete("cascade")->name("questions_current_assignee_id_foreign");
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
        Schema::table("questions", function (Blueprint $table) {
            $table->dropForeign("questions_user_id_foreign");
            $table->dropForeign("questions_current_assignee_id_foreign");
        });
        Schema::dropIfExists('questions');
    }
};
