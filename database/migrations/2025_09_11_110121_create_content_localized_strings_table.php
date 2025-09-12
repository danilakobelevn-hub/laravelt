<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_localized_strings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['name', 'description']);
            $table->char('locale', 2); // ru, en, etc.
            $table->text('value');
            $table->timestamps();

            $table->unique(['content_id', 'type', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_localized_strings');
    }
};
