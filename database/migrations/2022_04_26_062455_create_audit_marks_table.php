<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditMarksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_marks', function (Blueprint $table) {
            $table->id();
            $table->integer('week_id');
            $table->integer('aduitor_id');
            $table->integer('leader_id');
            $table->text('aduitMarks');
            $table->text('note')->nullable();
            $table->text('status');
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
        Schema::dropIfExists('audit_marks');
    }
}
