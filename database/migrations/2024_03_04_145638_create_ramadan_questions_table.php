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
        Schema::create('ramadan_questions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('link')->nullable();
            $table->text('question');
            $table->unsignedBigInteger('ramadan_day_id');
            $table->time('time_to_publish')->nullable();
            $table->string('category');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('ramadan_day_id')->references('id')->on('ramadan_days')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ramadan_questions');
    }
};
