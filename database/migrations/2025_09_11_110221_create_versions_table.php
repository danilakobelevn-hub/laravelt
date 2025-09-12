<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->onDelete('cascade');
            $table->string('platform');
            $table->unsignedInteger('major')->default(0);
            $table->unsignedInteger('minor')->default(0);
            $table->unsignedInteger('micro')->default(0);
            $table->boolean('tested')->default(false);
            $table->text('release_note')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->unique(['content_id', 'platform', 'major', 'minor', 'micro']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versions');
    }
};
