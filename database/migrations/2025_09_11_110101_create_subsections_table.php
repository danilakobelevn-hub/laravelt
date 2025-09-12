<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subsections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->string('alias'); // optics, mechanics...
            $table->string('default_name'); // Оптика, Механика...
            $table->text('default_description')->nullable();
            $table->timestamps();

            $table->unique(['section_id', 'alias']); // Уникальная связка раздел+подраздел
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsections');
    }
};
