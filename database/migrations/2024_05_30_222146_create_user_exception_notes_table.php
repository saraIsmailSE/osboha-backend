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
        Schema::create('user_exception_notes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_exception_id")->unsigned()->index();
            $table->foreign("user_exception_id")->references("id")->on("user_exceptions");
            $table->bigInteger("from_id")->unsigned()->index();
            $table->foreign("from_id")->references("id")->on("users");
            $table->text('body');
            // 0- unseen 1- seen
            $table->integer('status')->default(0);

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
        Schema::dropIfExists('user_exception_notes');
    }
};
