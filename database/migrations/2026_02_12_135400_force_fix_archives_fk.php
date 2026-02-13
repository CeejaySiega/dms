<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all existing foreign keys on archives table
        $existingFks = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'archives' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        
        // Drop all existing foreign keys
        foreach ($existingFks as $fk) {
            try {
                DB::statement("ALTER TABLE archives DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            } catch (\Throwable $e) {
                // Continue if FK doesn't exist
            }
        }
        
        // Add the correct separate foreign keys
        DB::statement('
            ALTER TABLE archives 
            ADD CONSTRAINT archives_user_id_foreign 
            FOREIGN KEY (user_id) REFERENCES users(user_id) 
            ON DELETE SET NULL
        ');
        
        DB::statement('
            ALTER TABLE archives 
            ADD CONSTRAINT archives_document_id_foreign 
            FOREIGN KEY (document_id) REFERENCES documents(document_id) 
            ON DELETE SET NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE archives DROP FOREIGN KEY archives_user_id_foreign');
        DB::statement('ALTER TABLE archives DROP FOREIGN KEY archives_document_id_foreign');
    }
};
