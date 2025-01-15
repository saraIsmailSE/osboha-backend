<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marthon_bonuses', function (Blueprint $table) {
            $table->integer('week_key')->after('osboha_marthon_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marthon_bonuses', function (Blueprint $table) {
            $table->dropIndex(['week_key']);
            $table->dropColumn('week_key');
        });
    }
};
