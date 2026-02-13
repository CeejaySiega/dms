<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sent_documents', function (Blueprint $table) {
            $table->string('file_path', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sent_documents', function (Blueprint $table) {
            $table->string('file_path', 191)->change();
        });
    }
};
