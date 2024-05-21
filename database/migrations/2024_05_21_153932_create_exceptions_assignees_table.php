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
        Schema::create('exceptions_assignees', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("assigned_by")->unsigned()->nullable();
            $table->bigInteger("exception_id")->unsigned();
            $table->bigInteger("assignee_id")->unsigned();
            $table->boolean("is_active")->default(true);

            $table->foreign("assigned_by")->references("id")->on("users")->onDelete("cascade")->name("exceptions_assignees_assigned_by_foreign");
            $table->foreign("exception_id")->references("id")->on("user_exceptions")->onDelete("cascade")->name("exceptions_assignees_exception_id_foreign");
            $table->foreign("assignee_id")->references("id")->on("users")->onDelete("cascade")->name("exceptions_assignees_assignee_id_foreign");
            $table->unique(["exception_id", "assignee_id"]);
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
        Schema::table("exceptions_assignees", function (Blueprint $table) {
            $table->dropForeign("exceptions_assignees_assigned_by_foreign");
            $table->dropForeign("exceptions_assignees_exception_id_foreign");
        });
        Schema::dropIfExists('exceptions_assignees');
    }
};
