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
            $table->integer('week_id')->nullable();
            $table->text('reason');
            $table->integer('type_id');
            $table->enum('status', ['pending', 'accepted', 'rejected','cancelled','finished'])->default('pending');
            $table->date('start_at')->nullable();
            $table->date('end_at')->nullable();
            $table->integer('reviewer_id')->nullable();
            $table->string('note')->nullable();
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
