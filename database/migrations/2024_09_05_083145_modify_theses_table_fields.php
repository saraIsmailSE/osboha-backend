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
        //change enum status in theses table to string
        //drop rejected_parts column
        Schema::table('theses', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
            $table->dropColumn('rejected_parts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //add rejected_parts column
        //change status column to enum
        Schema::table('theses', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'rejected', 'one_thesis'])->default('pending')->change();
            $table->enum('rejected_parts', [1, 2, 3, 4, 5])->nullable()->after('status');
        });
    }
};
