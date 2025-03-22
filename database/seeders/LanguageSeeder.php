<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $languages = [
            ['name' => 'English'],
            ['name' => 'Spanish'],
            ['name' => 'French'],
            ['name' => 'German'],
            ['name' => 'Mandarin'],
            ['name' => 'Hindi'],
            ['name' => 'Arabic'],
            ['name' => 'Portuguese'],
            ['name' => 'Bengali'],
            ['name' => 'Russian'],
        ];

        foreach ($languages as $language) {
            Language::firstOrCreate($language);
        }
    }
    }
