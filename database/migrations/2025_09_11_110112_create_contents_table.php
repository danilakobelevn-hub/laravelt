<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('alias')->unique();
            $table->string('default_name');
            $table->uuid('guid')->unique();
            $table->foreignId('subsection_id')->constrained()->onDelete('cascade'); // Связь с подразделом
            $table->unsignedTinyInteger('access_type')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
