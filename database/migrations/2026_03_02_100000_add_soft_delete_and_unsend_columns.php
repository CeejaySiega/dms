<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add deleted_at to recipients
        if (Schema::hasTable('recipients') && !Schema::hasColumn('recipients', 'deleted_at')) {
            Schema::table('recipients', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add unsend_at to document_routes
        if (Schema::hasTable('document_routes') && !Schema::hasColumn('document_routes', 'unsend_at')) {
            Schema::table('document_routes', function (Blueprint $table) {
                $table->timestamp('unsend_at')->nullable();
            });
        }

        // Add unsend_at and archive_at to received_documents
        if (Schema::hasTable('received_documents')) {
            Schema::table('received_documents', function (Blueprint $table) {
                if (!Schema::hasColumn('received_documents', 'unsend_at')) {
                    $table->timestamp('unsend_at')->nullable();
                }
                if (!Schema::hasColumn('received_documents', 'archive_at')) {
                    $table->timestamp('archive_at')->nullable();
                }
            });
        }

        // Add unsend_at to documents
        if (Schema::hasTable('documents') && !Schema::hasColumn('documents', 'unsend_at')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->timestamp('unsend_at')->nullable();
            });
        }

        // Add unsend_at to sent_documents
        if (Schema::hasTable('sent_documents') && !Schema::hasColumn('sent_documents', 'unsend_at')) {
            Schema::table('sent_documents', function (Blueprint $table) {
                $table->timestamp('unsend_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Implementation for rollback if needed
    }
};
