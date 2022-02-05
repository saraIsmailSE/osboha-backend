<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('week_id');
            $table->integer('out_of_90')->default(0);
            $table->integer('out_of_100')->default(0);
            $table->integer('total_pages')->default(0);
            $table->integer('support')->default(0);
            $table->integer('total_thesis')->default(0);
            $table->integer('total_screenshot')->default(0);
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
        Schema::dropIfExists('marks');
    }
}
