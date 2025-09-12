<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'alias' => 'physics',
                'default_name' => 'Физика',
                'default_description' => 'В этом разделе будут представлены сцены объясняющие законы физики.'
            ],
            [
                'alias' => 'chemistry',
                'default_name' => 'Химия',
                'default_description' => 'В этом разделе будут представлены сцены объясняющие законы химии, химические опыты и представлены химические соединения.'
            ],
            [
                'alias' => 'biology',
                'default_name' => 'Биология',
                'default_description' => 'В этом разделе будут представлены сцены объясняющие основы жизни.'
            ],
            [
                'alias' => 'history',
                'default_name' => 'История',
                'default_description' => 'В данном разделе будут представлены сцены демонстрирующие знаковые события в истории, а также различные архитектурные строения.'
            ],
            [
                'alias' => 'technologies',
                'default_name' => 'Технологии',
                'default_description' => 'В данном разделе будут представлены сцены объясняющие конструкцию и принцип работы различных механизмов и производств.'
            ],
            [
                'alias' => 'naturalSciences',
                'default_name' => 'Естественные науки',
                'default_description' => 'В данном разделе будут представлены сцены объясняющие устройство и законы окружающего мира.'
            ]
        ];

        foreach ($sections as $section) {
            DB::table('sections')->insert([
                'alias' => $section['alias'],
                'default_name' => $section['default_name'],
                'default_description' => $section['default_description'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
