<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRejectedMarksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rejected_marks', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('thesis_id');
            $table->integer('week_id');
            $table->integer('rejecter_id');
            $table->string('rejecter_note');
            $table->date('is_acceptable')->nullable();
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
        Schema::dropIfExists('rejected_marks');
    }
}
