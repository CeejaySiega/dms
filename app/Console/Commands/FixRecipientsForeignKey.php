<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixRecipientsForeignKey extends Command
{
    protected $signature = 'fix:recipients-fk';
    protected $description = 'Fix the recipients table foreign key constraint';

    public function handle()
    {
        try {
            $this->info('Dropping incorrect composite foreign key...');
            DB::statement('ALTER TABLE recipients DROP FOREIGN KEY fk_Recipients_Document_Routes1');
            $this->info('✓ Dropped composite foreign key');

            $this->info('Adding separate foreign keys...');
            DB::statement('ALTER TABLE recipients ADD CONSTRAINT fk_recipients_route_id FOREIGN KEY (route_id) REFERENCES document_routes(route_id) ON DELETE CASCADE');
            $this->info('✓ Added route_id foreign key');

            DB::statement('ALTER TABLE recipients ADD CONSTRAINT fk_recipients_user_id FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE');
            $this->info('✓ Added user_id foreign key');

            $this->info('Foreign key constraint fixed successfully!');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
