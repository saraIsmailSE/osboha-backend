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
        Schema::create('eligible_photos', function (Blueprint $table) {
            $table->id();
            $table->text('path');
            $table->bigInteger("thesis_id")->unsigned()->index();
            $table->foreign("thesis_id")->references("id")->on("thesis");
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
        Schema::dropIfExists('eligible_photos');
    }
};
