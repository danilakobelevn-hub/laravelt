<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('version_localizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained()->onDelete('cascade');
            $table->char('locale', 2);
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->unique(['version_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('version_localizations');
    }
};
