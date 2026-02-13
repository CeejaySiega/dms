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
        Schema::create('archives', function (Blueprint $table) {
            $table->id('archive_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('file_name', 50)->nullable();
            $table->timestamp('archive_at')->useCurrent();
            $table->timestamp('deleted_at')->nullable();
            
            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('document_id')->references('document_id')->on('documents')->nullOnDelete();
            
            $table->index('user_id');
            $table->index('document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archives');
    }
};
