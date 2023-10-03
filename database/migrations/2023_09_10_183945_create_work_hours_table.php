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
        Schema::create('work_hours', function (Blueprint $table) {
            $table->id();
            $table->integer("minutes")->default(0);
            $table->bigInteger("user_id")->unsigned()->index();
            $table->foreign("user_id")->references("id")->on("users")->onDelete("cascade");

            $table->unsignedBigInteger('week_id')->index();
            $table->foreign('week_id')->references('id')->on('weeks')->onDelete('cascade');
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
        Schema::dropIfExists('work_hours');
    }
};
