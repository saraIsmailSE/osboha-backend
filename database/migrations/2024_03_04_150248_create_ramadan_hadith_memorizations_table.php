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
        Schema::create('ramadan_hadith_memorizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ramadan_hadiths_id');
            $table->unsignedBigInteger('user_id');
            $table->text('hadith_memorize');
            $table->enum('status', ['pending', 'accepted', 'redo'])->default('pending');
            $table->integer('points')->default(0);
            $table->text('reviews')->nullable();
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->timestamp('redo_at')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('ramadan_hadiths_id')->references('id')->on('ramadan_hadiths')->onDelete('cascade');
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
        Schema::dropIfExists('ramadan_hadith_memorizations');
    }
};
