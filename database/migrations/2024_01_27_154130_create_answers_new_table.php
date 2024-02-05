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
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->text("answer");
            $table->bigInteger("user_id")->unsigned();
            $table->bigInteger("question_id")->unsigned();
            $table->boolean('is_discussion')->default(false);
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade")->name("answers_user_id_foreign");
            $table->foreign("question_id")->references("id")->on("questions")->onDelete("cascade")->name("answers_question_id_foreign");
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
        Schema::table("answers", function (Blueprint $table) {
            $table->dropForeign("answers_user_id_foreign");
            $table->dropForeign("answers_question_id_foreign");
        });
        Schema::dropIfExists('answers');
    }
};
