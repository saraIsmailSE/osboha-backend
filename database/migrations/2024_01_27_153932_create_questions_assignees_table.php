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
        Schema::create('questions_assignees', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("assigned_by")->unsigned()->nullable();
            $table->bigInteger("question_id")->unsigned();
            $table->bigInteger("assignee_id")->unsigned();
            $table->boolean("is_active")->default(true);

            $table->foreign("assigned_by")->references("id")->on("users")->onDelete("cascade")->name("questions_assignees_assigned_by_foreign");
            $table->foreign("question_id")->references("id")->on("questions")->onDelete("cascade")->name("questions_assignees_question_id_foreign");
            $table->foreign("assignee_id")->references("id")->on("users")->onDelete("cascade")->name("questions_assignees_assignee_id_foreign");
            $table->unique(["question_id", "assignee_id"]);
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
        Schema::table("questions_assignees", function (Blueprint $table) {
            $table->dropForeign("questions_assignees_assigned_by_foreign");
            $table->dropForeign("questions_assignees_question_id_foreign");
        });
        Schema::dropIfExists('questions_assignees');
    }
};
