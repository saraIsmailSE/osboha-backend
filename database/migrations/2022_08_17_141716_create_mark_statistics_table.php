<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarkStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mark_statistics', function (Blueprint $table) {
            $table->id();
            $table->integer('general_average_reeding')->default(0);
            $table->integer('total_users_have_100')->default(0);
            $table->integer('total_pages')->default(0);
            $table->integer('total_thesis')->default(0);
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
        Schema::dropIfExists('mark_statistics');
    }
}
