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
        Schema::create('audit_notes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("mark_for_audit_id")->unsigned()->index();
            $table->foreign("mark_for_audit_id")->references("id")->on("marks_for_audits");
            $table->integer('from_id');
            $table->integer('to_id');
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
        Schema::table('audit_notes', function (Blueprint $table) {
            $table->dropForeign('audit_marks_id');
        });
        Schema::dropIfExists('audit_notes');
    }
};