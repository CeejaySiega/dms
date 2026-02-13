<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old foreign keys (including composite constraint) if they exist.
        try {
            DB::statement('ALTER TABLE archives DROP FOREIGN KEY fk_Archives_Documents1');
        } catch (\Throwable $e) {
            // Ignore if the constraint does not exist.
        }

        try {
            Schema::table('archives', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Throwable $e) {
            // Ignore if the constraint does not exist.
        }

        try {
            Schema::table('archives', function (Blueprint $table) {
                $table->dropForeign(['document_id']);
            });
        } catch (\Throwable $e) {
            // Ignore if the constraint does not exist.
        }

        Schema::table('archives', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('document_id')
                ->references('document_id')
                ->on('documents')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archives', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['document_id']);
        });

        // Restore the composite foreign key for safety.
        Schema::table('archives', function (Blueprint $table) {
            $table->foreign(['document_id', 'user_id'], 'fk_Archives_Documents1', 'fk_Archives_Users1')
                ->references(['document_id', 'user_id'])
                ->on('documents','users')
                ->nullOnDelete();
        });
    }
};
