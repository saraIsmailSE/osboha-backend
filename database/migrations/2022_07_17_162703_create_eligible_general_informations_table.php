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
        Schema::create('eligible_general_informations', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned()->index();
            $table->text('general_question');
            $table->text('summary');
            $table->text("reviews")->nullable();
            $table->integer('degree')->nullable();

            $table->enum('status', ['ready','audit', 'review', 'audited','rejected','retard'])->nullable();

            $table->bigInteger('reviewer_id')->unsigned()->nullable();
            $table->bigInteger('auditor_id')->unsigned()->nullable();
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
        Schema::dropIfExists('eligible_general_informations');
    }
};
