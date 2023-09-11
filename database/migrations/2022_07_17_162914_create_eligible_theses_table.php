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
        Schema::create('eligible_general_thesis', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned()->index();
            $table->text('thesis_text');
            $table->text('starting_page');
            $table->text('ending_page');
            $table->text("reviews")->nullable();
            $table->enum('status', ['accept','ready','audit', 'review', 'audited','rejected','retard'])->nullable();
            $table->integer('degree')->nullable();
            $table->bigInteger('reviewer_id')->unsigned()->nullable();
            $table->bigInteger('auditor_id')->unsigned()->nullable();
            $table->bigInteger("user_book_id")->unsigned()->index();
            $table->foreign("user_book_id")->references("id")->on("user_book");

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
        Schema::dropIfExists('eligible_general_thesis');
    }
};
