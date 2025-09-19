<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ConvertSubsectionsToTreeStructure extends Migration
{
    public function up()
    {
        // 1. Добавляем поля для древовидной структуры в sections (если их нет)
        Schema::table('sections', function (Blueprint $table) {
            if (!Schema::hasColumn('sections', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->constrained('sections')->onDelete('cascade');
            }
            if (!Schema::hasColumn('sections', 'order')) {
                $table->integer('order')->default(0);
            }
            if (!Schema::hasColumn('sections', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        // 2. Временно отключаем foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 3. Переносим подразделы в sections как дочерние элементы
        $subsections = DB::table('subsections')->get();

        foreach ($subsections as $subsection) {
            // Проверяем, существует ли уже раздел с таким alias и parent_id
            $existingSection = DB::table('sections')
                ->where('alias', $subsection->alias)
                ->where('parent_id', $subsection->section_id)
                ->first();

            if (!$existingSection) {
                $newSectionId = DB::table('sections')->insertGetId([
                    'alias' => $subsection->alias,
                    'default_name' => $subsection->default_name,
                    'default_description' => $subsection->default_description,
                    'parent_id' => $subsection->section_id,
                    'order' => 0,
                    'is_active' => true,
                    'created_at' => $subsection->created_at,
                    'updated_at' => $subsection->updated_at,
                ]);

                // 4. Обновляем контенты, которые ссылались на этот подраздел
                DB::table('contents')
                    ->where('subsection_id', $subsection->id)
                    ->update([
                        'section_id' => $newSectionId,
                        'subsection_id' => null
                    ]);
            }
        }

        // 5. Удаляем foreign key constraint из contents перед удалением таблицы subsections
        Schema::table('contents', function (Blueprint $table) {
            // Получаем информацию о foreign keys
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys('contents');

            foreach ($foreignKeys as $foreignKey) {
                if (in_array('subsection_id', $foreignKey->getLocalColumns())) {
                    $table->dropForeign([$foreignKey->getLocalColumns()[0]]);
                }
            }
        });

        // 6. Удаляем столбец subsection_id из contents
        Schema::table('contents', function (Blueprint $table) {
            if (Schema::hasColumn('contents', 'subsection_id')) {
                $table->dropColumn('subsection_id');
            }
        });

        // 7. Теперь безопасно удаляем таблицу подразделов
        Schema::dropIfExists('subsections');

        // 8. Включаем foreign key checks обратно
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down()
    {
        // Временно отключаем foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Создаем таблицу подразделов обратно
        Schema::create('subsections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->string('alias');
            $table->string('default_name');
            $table->text('default_description')->nullable();
            $table->timestamps();

            $table->unique(['section_id', 'alias']);
        });

        // Восстанавливаем данные подразделов из дочерних разделов
        $childSections = DB::table('sections')->whereNotNull('parent_id')->get();

        foreach ($childSections as $childSection) {
            DB::table('subsections')->insert([
                'section_id' => $childSection->parent_id,
                'alias' => $childSection->alias,
                'default_name' => $childSection->default_name,
                'default_description' => $childSection->default_description,
                'created_at' => $childSection->created_at,
                'updated_at' => $childSection->updated_at,
            ]);
        }

        // Добавляем столбец subsection_id обратно в contents
        Schema::table('contents', function (Blueprint $table) {
            if (!Schema::hasColumn('contents', 'subsection_id')) {
                $table->foreignId('subsection_id')->nullable()->constrained()->onDelete('cascade');
            }
        });

        // Восстанавливаем связи контента (упрощенная версия)
        $subsections = DB::table('subsections')->get();
        foreach ($subsections as $subsection) {
            DB::table('contents')
                ->where('section_id', $subsection->id)
                ->update(['subsection_id' => $subsection->id]);
        }

        // Удаляем древовидные поля из sections
        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn(['parent_id', 'order', 'is_active']);
            }
        });

        // Включаем foreign key checks обратно
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
