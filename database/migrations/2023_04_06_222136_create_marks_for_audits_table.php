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
        Schema::create('marks_for_audits', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("audit_marks_id")->unsigned()->index();
            $table->foreign("audit_marks_id")->references("id")->on("audit_marks");
            $table->bigInteger("mark_id")->unsigned()->index();
            $table->foreign("mark_id")->references("id")->on("marks");
            // acceptable or unacceptable
            $table->enum('status', ['acceptable', 'unacceptable', 'not_audited'])->default('not_audited');
            $table->bigInteger("type_id")->unsigned()->index();
            $table->foreign("type_id")->references("id")->on("audit_types");
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
        Schema::table('marks_for_audits', function (Blueprint $table) {
            $table->dropForeign('audit_marks_id');
            $table->dropForeign('mark_id');
            $table->dropForeign('type_id');
        });
        Schema::dropIfExists('marks_for_audits');
    }
};