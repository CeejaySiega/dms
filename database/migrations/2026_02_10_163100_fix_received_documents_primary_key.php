<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('received_documents')) {
            return;
        }

        if (Schema::hasColumn('received_documents', 'sent_id')) {
            // Rename sent_id to received_id and make it AUTO_INCREMENT.
            DB::statement(
                'ALTER TABLE received_documents CHANGE sent_id received_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
        } elseif (Schema::hasColumn('received_documents', 'received_id')) {
            // Ensure received_id is AUTO_INCREMENT.
            DB::statement(
                'ALTER TABLE received_documents MODIFY received_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
        }

        // Ensure primary key exists on received_id.
        $primary = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'received_documents' AND CONSTRAINT_TYPE = 'PRIMARY KEY'"
        );

        if (empty($primary)) {
            DB::statement(
                'ALTER TABLE received_documents ADD PRIMARY KEY (received_id)'
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('received_documents')) {
            return;
        }

        if (Schema::hasColumn('received_documents', 'received_id')) {
            DB::statement(
                'ALTER TABLE received_documents MODIFY received_id BIGINT UNSIGNED NOT NULL'
            );

            if (!Schema::hasColumn('received_documents', 'sent_id')) {
                DB::statement(
                    'ALTER TABLE received_documents CHANGE received_id sent_id BIGINT UNSIGNED NOT NULL'
                );
            }
        }

        // Drop primary key if it exists.
        $primary = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'received_documents' AND CONSTRAINT_TYPE = 'PRIMARY KEY'"
        );

        if (!empty($primary)) {
            DB::statement('ALTER TABLE received_documents DROP PRIMARY KEY');
        }
    }
};
