<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->comment('Public identifier for the signed download URL');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('fileable');
            $table->string('document_type', 60)->comment('Technical Report, Resolution, Certification, Program, ...');
            $table->string('original_name');
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->char('hash_sha256', 64)->comment('Integrity check and duplicate detection');
            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index('document_type');
        });

        DB::statement('ALTER TABLE files ADD CONSTRAINT chk_files_size_bytes CHECK (size_bytes > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
