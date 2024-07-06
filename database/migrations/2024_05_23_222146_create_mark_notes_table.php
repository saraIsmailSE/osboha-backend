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
        Schema::create('mark_notes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("mark_id")->unsigned()->index();
            $table->foreign("mark_id")->references("id")->on("marks");
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
        Schema::dropIfExists('mark_notes');
    }
};
