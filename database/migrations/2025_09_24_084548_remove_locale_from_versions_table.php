<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('versions', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }

    public function down()
    {
        Schema::table('versions', function (Blueprint $table) {
            $table->char('locale', 2)->default('ru');
        });
    }
};
