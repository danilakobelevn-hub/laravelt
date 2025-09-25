<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('versions', function (Blueprint $table) {
            if (Schema::hasTable('version_localizations')) {
                Schema::drop('version_localizations');
            }

            $table->string('locale')->default('ru')->after('platform');
            $table->string('localization_file_name')->nullable()->after('file_path');
            $table->string('localization_file_path')->nullable()->after('localization_file_name');
            $table->bigInteger('localization_file_size')->nullable()->after('localization_file_path');
        });
    }

    public function down()
    {
        Schema::table('versions', function (Blueprint $table) {
            $table->dropColumn([
                'locale',
                'localization_file_name',
                'localization_file_path',
                'localization_file_size'
            ]);
        });
    }
};
