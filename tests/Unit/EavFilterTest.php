<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Attribute;
use Illuminate\Support\Facades\DB;

class EavFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \DB::table('product_attributes')->truncate();
    }

    public function test_filter_by_integer_range(): void
    {
        $widthAttr = Attribute::where('code', 'width')->firstOrFail();

        // Берём два продукта из базы (после сидеров)
        $p1 = Product::firstOrFail();
        $p2 = Product::where('id', '!=', $p1->id)->firstOrFail();

        // Привязываем значения
        DB::table('product_attributes')->insert([
            ['product_id' => $p1->id, 'attribute_id' => $widthAttr->id, 'value_int' => 15],
            ['product_id' => $p2->id, 'attribute_id' => $widthAttr->id, 'value_int' => 50],
        ]);

        $products = Product::whereHas('attributes', function ($q) use ($widthAttr) {
            $q->where('attribute_id', $widthAttr->id)
              ->whereBetween('value_int', [10, 20]);
        })->get();

        $this->assertCount(1, $products);
        $this->assertSame($p1->id, $products->first()->id);
    }

    public function test_filter_by_string_value(): void
    {
        $colorAttr = Attribute::where('code', 'color')->firstOrFail();

        // Берём два продукта из базы (после сидеров)
        $p1 = Product::firstOrFail();
        $p2 = Product::where('id', '!=', $p1->id)->firstOrFail();

        // Привязываем значения
        DB::table('product_attributes')->insert([
            ['product_id' => $p1->id, 'attribute_id' => $colorAttr->id, 'value_string' => 'red'],
            ['product_id' => $p2->id, 'attribute_id' => $colorAttr->id, 'value_string' => 'blue'],
        ]);

        $products = Product::whereHas('attributes', function ($q) use ($colorAttr) {
            $q->where('attribute_id', $colorAttr->id)
              ->where('value_string', 'red');
        })->get();

        $this->assertCount(1, $products);
        $this->assertSame($p1->id, $products->first()->id);
    }
}
