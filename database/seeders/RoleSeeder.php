<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'alias' => 'supervisor',
                'name' => 'Администратор',
                'description' => 'Может все'
            ],
            [
                'alias' => 'developer',
                'name' => 'Разработчик',
                'description' => 'Не может изменять данные на сервере, кроме загрузки новой версии контента. Все остальное может как пользователь с активной подпиской.'
            ],
            [
                'alias' => 'qa',
                'name' => 'Тестировщик контента',
                'description' => 'Не может изменять данные на сервере, кроме пометки контента как оттестированный. Все остальное может как пользователь с активной подпиской, но получает список всех неоттестированных контентов на запрос получения всех контентов. Также при скачивании контента получает последнюю неоттестированную версию.'
            ],
            [
                'alias' => 'user',
                'name' => 'Пользователь',
                'description' => 'Может получать список всех контентов, но скачивать только демо контент'
            ],
            [
                'alias' => 'subscribe',
                'name' => 'Пользователь с подпиской',
                'description' => 'Может получать список всех контентов и их загружать.'
            ]
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert([
                'alias' => $role['alias'],
                'name' => $role['name'],
                'description' => $role['description'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
