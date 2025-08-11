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
        Schema::table('user_exceptions', function (Blueprint $table) {
            $table->unsignedBigInteger('requested_by')->nullable()->after('user_id');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_exceptions', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropColumn('requested_by');
        });
    }
};
