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
            $table->enum("status", ["open", "closed", "solved", "discussion"])->default("open");
            $table->bigInteger("user_id")->unsigned();
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->bigInteger("assignee_id")->unsigned()->nullable();
            $table->foreign("assignee_id")->references("id")->on("users")->onDelete("cascade");
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
            $table->dropForeign(["user_id"]);
            $table->dropForeign(["assignee_id"]);
        });
        Schema::dropIfExists('questions');
    }
};
