<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubsectionSeeder extends Seeder
{
    public function run(): void
    {
        // Получаем ID разделов
        $physicsId = DB::table('sections')->where('alias', 'physics')->value('id');
        $chemistryId = DB::table('sections')->where('alias', 'chemistry')->value('id');
        $biologyId = DB::table('sections')->where('alias', 'biology')->value('id');
        $historyId = DB::table('sections')->where('alias', 'history')->value('id');
        $technologiesId = DB::table('sections')->where('alias', 'technologies')->value('id');
        $naturalSciencesId = DB::table('sections')->where('alias', 'naturalSciences')->value('id');

        $subsections = [
            // Физика
            ['section_id' => $physicsId, 'alias' => 'optics', 'default_name' => 'Оптика'],
            ['section_id' => $physicsId, 'alias' => 'mechanics', 'default_name' => 'Механика'],
            ['section_id' => $physicsId, 'alias' => 'thermodynamics', 'default_name' => 'Термодинамика'],
            ['section_id' => $physicsId, 'alias' => 'electricity', 'default_name' => 'Электричество'],
            ['section_id' => $physicsId, 'alias' => 'nuclearPhysics', 'default_name' => 'Ядерная физика'],

            // Химия
            ['section_id' => $chemistryId, 'alias' => 'basics', 'default_name' => 'Основы химии'],
            ['section_id' => $chemistryId, 'alias' => 'organic', 'default_name' => 'Органическая химия'],
            ['section_id' => $chemistryId, 'alias' => 'inorganic', 'default_name' => 'Неорганическая химия'],
            ['section_id' => $chemistryId, 'alias' => 'chemicalReactions', 'default_name' => 'Химические реакции'],
            ['section_id' => $chemistryId, 'alias' => 'chemicalTechnologies', 'default_name' => 'Химические технологии'],

            // Биология
            ['section_id' => $biologyId, 'alias' => 'humanAnatomy', 'default_name' => 'Анатомия человека'],
            ['section_id' => $biologyId, 'alias' => 'animals', 'default_name' => 'Животные'],
            ['section_id' => $biologyId, 'alias' => 'plants', 'default_name' => 'Растения'],
            ['section_id' => $biologyId, 'alias' => 'microbiology', 'default_name' => 'Микробиология'],

            // История
            ['section_id' => $historyId, 'alias' => 'primitiveWorld', 'default_name' => 'Первобытный мир'],
            ['section_id' => $historyId, 'alias' => 'ancientWorld', 'default_name' => 'Древний мир'],
            ['section_id' => $historyId, 'alias' => 'middleAges', 'default_name' => 'Средние века'],
            ['section_id' => $historyId, 'alias' => 'newHistory', 'default_name' => 'Новая история'],
            ['section_id' => $historyId, 'alias' => 'modernHistory', 'default_name' => 'Новейшая история'],

            // Технологии
            ['section_id' => $technologiesId, 'alias' => 'transport', 'default_name' => 'Транспорт'],
            ['section_id' => $technologiesId, 'alias' => 'devices', 'default_name' => 'Устройства'],
            ['section_id' => $technologiesId, 'alias' => 'production', 'default_name' => 'Производство'],

            // Естественные науки
            ['section_id' => $naturalSciencesId, 'alias' => 'worldAroundUs', 'default_name' => 'Окружающий мир'],
            ['section_id' => $naturalSciencesId, 'alias' => 'astronomy', 'default_name' => 'Астрономия'],
            ['section_id' => $naturalSciencesId, 'alias' => 'geography', 'default_name' => 'География']
        ];

        foreach ($subsections as $subsection) {
            DB::table('subsections')->insert([
                'section_id' => $subsection['section_id'],
                'alias' => $subsection['alias'],
                'default_name' => $subsection['default_name'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
