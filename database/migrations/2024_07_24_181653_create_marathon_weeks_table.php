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
        Schema::create('marathon_weeks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('osboha_marthon_id')->index();
            $table->foreign('osboha_marthon_id')->references('id')->on('osboha_marthons')->onDelete('cascade');
            $table->boolean("is_active")->default(0);
            $table->integer('week_key')->index();
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
        Schema::dropIfExists('marathon_weeks');
    }
};
