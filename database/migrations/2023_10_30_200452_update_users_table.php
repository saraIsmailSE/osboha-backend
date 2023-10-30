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
        Schema::table('users', function (Blueprint $table) {
            /*
            * allowed_to_eligible 
            * 2=> can edit his info
            * 1 => allowed
            * 0 => waiting for approval
            */
            $table->boolean('allowed_to_eligible')->default(2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('books', function (Blueprint $table) {
        //     $table->string('link')->change();
        // });
    }
};
