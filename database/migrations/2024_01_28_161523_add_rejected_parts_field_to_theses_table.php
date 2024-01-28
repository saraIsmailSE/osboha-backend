<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        DB::statement("ALTER TABLE theses MODIFY status ENUM('pending', 'accepted', 'rejected', 'one_thesis', 'rejected_parts') DEFAULT 'pending';");
        Schema::table('theses', function (Blueprint $table) {
            $table->enum('rejected_parts', [1, 2, 3, 4, 5])->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE theses MODIFY status ENUM('pending', 'accepted', 'rejected', 'one_thesis') DEFAULT 'pending';");
        Schema::table('theses', function (Blueprint $table) {
            $table->dropColumn('rejected_parts');
        });
    }
};
