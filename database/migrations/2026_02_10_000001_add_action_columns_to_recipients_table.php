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
        Schema::table('recipients', function (Blueprint $table) {
            if (!Schema::hasColumn('recipients', 'action')) {
                $table->string('action', 50)->nullable()->after('role');
            }
            if (!Schema::hasColumn('recipients', 'approve_at')) {
                $table->timestamp('approve_at')->nullable()->after('action');
            }
            if (!Schema::hasColumn('recipients', 'receive_at')) {
                $table->timestamp('receive_at')->nullable()->after('approve_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipients', function (Blueprint $table) {
            if (Schema::hasColumn('recipients', 'receive_at')) {
                $table->dropColumn('receive_at');
            }
            if (Schema::hasColumn('recipients', 'approve_at')) {
                $table->dropColumn('approve_at');
            }
            if (Schema::hasColumn('recipients', 'action')) {
                $table->dropColumn('action');
            }
        });
    }
};