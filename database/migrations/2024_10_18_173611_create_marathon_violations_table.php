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
        Schema::create('marathon_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osboha_marthon_id')->constrained('osboha_marthons')->onDelete('cascade');
            $table->integer('week_key')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reason_id')->constrained('marathon_violations_reasons')->onDelete('cascade');
            $table->text('reviewer_note');
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
        Schema::dropIfExists('marathon_violations');
    }
};
