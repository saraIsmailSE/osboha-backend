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
        Schema::create('ramadan_questions_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ramadan_question_id');
            $table->unsignedBigInteger('user_id');
            $table->text('answer');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->integer('points')->default(0);
            $table->text('reviews')->nullable();
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('ramadan_question_id')->references('id')->on('ramadan_questions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ramadan_questions_answers');
    }
};
