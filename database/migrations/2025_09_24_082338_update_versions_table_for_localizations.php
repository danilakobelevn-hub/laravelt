<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateVersionsTableForLocalizations extends Migration
{
    public function up()
    {
        Schema::table('versions', function (Blueprint $table) {
            if (Schema::hasColumn('versions', 'localization_file_name')) {
                $table->dropColumn('localization_file_name');
            }
            if (Schema::hasColumn('versions', 'localization_file_path')) {
                $table->dropColumn('localization_file_path');
            }
            if (Schema::hasColumn('versions', 'localization_file_size')) {
                $table->dropColumn('localization_file_size');
            }

            $table->char('locale', 2)->default('ru')->change();
        });

        if (!Schema::hasTable('version_localizations')) {
            Schema::create('version_localizations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('version_id')->constrained()->onDelete('cascade');
                $table->char('locale', 2); // ru, en и т.д.
                $table->string('file_name');
                $table->string('file_path');
                $table->bigInteger('file_size');
                $table->timestamps();

                $table->unique(['version_id', 'locale']);
            });
        }
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
