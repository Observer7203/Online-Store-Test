<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{

    public function test_products_list_with_pagination()
    {
        $this->withoutExceptionHandling();
        $response = $this->getJson('/api/products?page=1');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_filter_products_by_category()
    {
        $this->withoutExceptionHandling();
        $category = Category::where('depth', 3)->firstOrFail();

        $response = $this->getJson("/api/products?category_ids[]={$category->id}");

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_filter_products_by_price_range()
    {
        $this->withoutExceptionHandling();
        $response = $this->getJson('/api/products?price_min=1000&price_max=100000');

        $response->assertStatus(200);
    }
}
