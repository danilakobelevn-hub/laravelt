<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_localized_strings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['name', 'description']);
            $table->char('locale', 2);
            $table->text('value');
            $table->timestamps();

            $table->unique(['module_id', 'type', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_localized_strings');
    }
};
