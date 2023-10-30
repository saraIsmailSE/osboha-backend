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
        Schema::create('eligible_quotations', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->bigInteger("eligible_question_id")->unsigned()->index()->nullable();
            $table->foreign("eligible_question_id")->references("id")->on("eligible_questions");
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
        Schema::dropIfExists('eligible_quotations');
    }
};
