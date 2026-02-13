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
        Schema::create('recipients', function (Blueprint $table) {
            $table->bigIncrements('recipient_id');
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role', 50)->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->foreign('route_id')
                ->references('route_id')
                ->on('document_routes')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();

            // Composite index for faster lookups
            $table->unique(['route_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipients');
    }
};
