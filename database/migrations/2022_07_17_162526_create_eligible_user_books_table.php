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
        Schema::create('eligible_user_books', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned()->index();
            $table->enum('status', ['review', 'audit','audited','rejected','retard','finished'])->nullable();
            $table->text("reviews")->nullable();
            $table->bigInteger("user_id")->unsigned()->index();
            $table->foreign("user_id")->references("id")->on("users");
            $table->bigInteger("book_id")->unsigned()->index();
            $table->foreign("book_id")->references("id")->on("books");
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
        Schema::dropIfExists('user_book');
    }
};
