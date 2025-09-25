<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorVersionLocalizations extends Migration
{
    public function up()
    {
        // Создаем новую таблицу для локализаций версий
        Schema::create('version_localizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained()->onDelete('cascade');
            $table->char('locale', 2); // Используем char(2) для кода языка
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->timestamps();

            $table->unique(['version_id', 'locale']); // Одна локализация на язык для версии
        });

        Schema::table('versions', function (Blueprint $table) {
            $table->dropColumn([
                'localization_file_name',
                'localization_file_path',
                'localization_file_size'
            ]);

            $table->char('locale', 2)->default('ru')->change();
        });
    }

    public function down()
    {
        Schema::table('versions', function (Blueprint $table) {
            $table->string('localization_file_name')->nullable();
            $table->string('localization_file_path')->nullable();
            $table->bigInteger('localization_file_size')->nullable();
            $table->string('locale', 255)->default('ru')->change();
        });

        Schema::dropIfExists('version_localizations');
    }
}
