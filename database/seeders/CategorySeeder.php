<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $rootCategories = [
            'Одежда' => ['Мужская' => ['Куртки', 'Брюки'], 'Женская' => ['Платья', 'Юбки']],
            'Обувь' => ['Мужская' => ['Кроссовки', 'Ботинки'], 'Женская' => ['Туфли', 'Сандалии']],
        ];

        foreach ($rootCategories as $root => $subs) {
            $rootCategory = Category::create([
                'name' => $root,
                'slug' => Str::slug($root),
                'parent_id' => null,
                'depth' => 1,
            ]);

            foreach ($subs as $subName => $children) {
                $subCategory = Category::create([
                    'name' => $subName,
                    'slug' => Str::slug($root . '-' . $subName),
                    'parent_id' => $rootCategory->id,
                    'depth' => 2,
                ]);

                foreach ($children as $child) {
                    Category::create([
                        'name' => $child,
                        'slug' => Str::slug($root . '-' . $subName . '-' . $child),
                        'parent_id' => $subCategory->id,
                        'depth' => 3,
                    ]);
                }
            }
        }
    }
}
