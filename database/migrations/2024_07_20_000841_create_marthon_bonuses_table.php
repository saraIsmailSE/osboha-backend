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
        Schema::create('marthon_bonuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('osboha_marthon_id')->index();
            $table->foreign('osboha_marthon_id')->references('id')->on('osboha_marthons')->onDelete('cascade');
            $table->integer('activity')->nullable();;
            $table->integer('leading_course')->nullable();;
            $table->integer('eligible_book')->nullable();;//very good and above
            $table->integer('eligible_book_less_VG')->nullable();;//less than very good
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
        Schema::dropIfExists('marthon_bonuses');
    }
};
