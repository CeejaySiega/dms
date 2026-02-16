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
        // Drop the problematic composite foreign key if it exists
        try {
            DB::statement('ALTER TABLE archives DROP FOREIGN KEY fk_Archives_Documents1');
            echo "Dropped composite foreign key fk_Archives_Documents1\n";
        } catch (\Throwable $e) {
            echo "Composite foreign key fk_Archives_Documents1 doesn't exist or already dropped\n";
        }

        // Check and add individual foreign keys if they don't exist
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'archives' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        $constraintNames = array_map(fn($c) => $c->CONSTRAINT_NAME, $constraints);

        // Add user_id foreign key if it doesn't exist
        if (!in_array('archives_user_id_foreign', $constraintNames)) {
            try {
                DB::statement('
                    ALTER TABLE archives 
                    ADD CONSTRAINT archives_user_id_foreign 
                    FOREIGN KEY (user_id) REFERENCES users(user_id) 
                    ON DELETE SET NULL
                ');
                echo "Added archives_user_id_foreign\n";
            } catch (\Throwable $e) {
                echo "Could not add archives_user_id_foreign: " . $e->getMessage() . "\n";
            }
        }

        // Add document_id foreign key if it doesn't exist
        if (!in_array('archives_document_id_foreign', $constraintNames)) {
            try {
                DB::statement('
                    ALTER TABLE archives 
                    ADD CONSTRAINT archives_document_id_foreign 
                    FOREIGN KEY (document_id) REFERENCES documents(document_id) 
                    ON DELETE SET NULL
                ');
                echo "Added archives_document_id_foreign\n";
            } catch (\Throwable $e) {
                echo "Could not add archives_document_id_foreign: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is a fix, so down() is intentionally empty
        // We don't want to restore the broken composite key
    }
};
