<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_available_locales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->onDelete('cascade');
            $table->char('locale', 2);
            $table->timestamps();

            $table->unique(['content_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_available_locales');
    }
};
