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
        Schema::create('user_week_activities', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id")->unsigned();
            $table->bigInteger("week_id")->unsigned();
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade")->name("user_week_activities_user_id_foreign");
            $table->foreign("week_id")->references("id")->on("weeks")->onDelete("cascade")->name("user_week_activities_week_id_foreign");
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
        Schema::table("user_week_activities", function (Blueprint $table) {
            $table->dropForeign("user_week_activities_user_id_foreign");
            $table->dropForeign("user_week_activities_week_id_foreign");
        });
        Schema::dropIfExists('user_week_activities');
    }
};
