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
        Schema::create('eligible_certificates', function (Blueprint $table) {
            $table->id('id')->unsigned()->index();
            $table->integer('final_grade');
            $table->integer('general_summary_grade');
            $table->integer('thesis_grade');
            $table->integer('check_reading_grade');
            $table->bigInteger("eligible_user_books_id")->unsigned()->index();
            $table->foreign("eligible_user_books_id")->references("id")->on("eligible_user_books");
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
        Schema::dropIfExists('eligible_certificates');
    }
};
