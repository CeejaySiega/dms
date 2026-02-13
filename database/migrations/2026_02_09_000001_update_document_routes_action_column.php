<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('document_routes') && Schema::hasColumn('document_routes', 'action')) {
            DB::statement('ALTER TABLE document_routes MODIFY action VARCHAR(50) NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('document_routes') && Schema::hasColumn('document_routes', 'action')) {
            DB::statement('ALTER TABLE document_routes MODIFY action VARCHAR(50) NULL');
        }
    }
};
