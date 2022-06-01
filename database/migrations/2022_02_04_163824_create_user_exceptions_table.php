<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserExceptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_exceptions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('week_id');
            $table->string('reason');
            $table->integer('type_id');
            $table->integer('duration'); // in days
            $table->string('status')->default('pending');
            $table->date('start_at')->nullable();
            $table->string('leader_note')->nullable();
            $table->string('advisor_note')->nullable();
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
        Schema::dropIfExists('user_exceptions');
    }
}
