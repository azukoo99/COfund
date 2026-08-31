<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoriesData = [
            ['name' => 'Teknologi & Inovasi', 'slug' => 'teknologi-inovasi'],
            ['name' => 'Pendidikan', 'slug' => 'pendidikan'],
            ['name' => 'Sosial & Kemanusiaan', 'slug' => 'sosial-kemanusiaan'],
            ['name' => 'Seni & Kreatif', 'slug' => 'seni-kreatif'],
            ['name' => 'Lingkungan Hidup', 'slug' => 'lingkungan-hidup'],
            ['name' => 'Medis & Kesehatan', 'slug' => 'medis-kesehatan'],
        ];

        foreach ($categoriesData as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }
    }
}
