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
        Schema::table('document_routes', function (Blueprint $table) {
            // Add new columns for sender/receiver pattern
            $table->unsignedBigInteger('sender_id')->nullable()->after('document_id');
            $table->unsignedBigInteger('receiver_id')->nullable()->after('sender_id');
            $table->timestamp('forward_at')->nullable()->after('unsend_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_routes', function (Blueprint $table) {
            if (Schema::hasColumn('document_routes', 'forward_at')) {
                $table->dropColumn('forward_at');
            }
            if (Schema::hasColumn('document_routes', 'receiver_id')) {
                $table->dropColumn('receiver_id');
            }
            if (Schema::hasColumn('document_routes', 'sender_id')) {
                $table->dropColumn('sender_id');
            }
        });
    }
};
