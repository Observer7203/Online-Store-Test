<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\ProductAttribute;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('TRUNCATE products RESTART IDENTITY CASCADE;');
        // Создаём несколько атрибутов
        $color = Attribute::firstOrCreate(['code' => 'color'], [
            'name' => 'Цвет',
            'type' => 'string',
        ]);

        $size = Attribute::firstOrCreate(['code' => 'size'], [
            'name' => 'Размер',
            'type' => 'string',
        ]);

        $width = Attribute::firstOrCreate(['code' => 'width'], [
            'name' => 'Ширина',
            'type' => 'integer',
        ]);

        $categories = Category::whereIn('depth', [2, 3])->get();

        foreach (range(1, 50) as $i) {
            $category = $categories->random();

            $product = Product::create([
                'name' => "Product $i",
                'description' => "Описание товара $i",
                'slug' => Str::slug("product-$i"),
                'category_id' => $category->id,
                'price_minor' => rand(1000, 100000),
            ]);

            // Randomly assign attributes
            ProductAttribute::create([
                'product_id' => $product->id,
                'attribute_id' => $color->id,
                'value_string' => ['red', 'blue', 'green'][array_rand(['red', 'blue', 'green'])],
            ]);

            ProductAttribute::create([
                'product_id' => $product->id,
                'attribute_id' => $size->id,
                'value_string' => ['S', 'M', 'L'][array_rand(['S', 'M', 'L'])],
            ]);

            ProductAttribute::create([
                'product_id' => $product->id,
                'attribute_id' => $width->id,
                'value_int' => rand(10, 50),
            ]);
        }
    }
}
