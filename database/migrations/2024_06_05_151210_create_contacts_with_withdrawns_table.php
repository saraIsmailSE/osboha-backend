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
        Schema::create('contacts_with_withdrawns', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('contact');
            $table->tinyInteger('return');
            $table->unsignedBigInteger('reviewer_id');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('contacts_with_withdrawns');
    }
};
