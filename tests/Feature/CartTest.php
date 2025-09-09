<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use App\Models\Cart;

class CartTest extends TestCase
{
       public function test_guest_can_add_item_to_cart()
    {
        $product = Product::firstOrFail();

        $response = $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'qty' => 2,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'guest_token',
                         'total_minor',
                         'items' => [
                             '*' => [
                                 'id',
                                 'product_id',
                                 'qty',
                                 'price_minor',
                                 'product' => [
                                     'id',
                                     'name',
                                     'slug'
                                 ]
                             ]
                         ]
                     ]
                 ]);

        // check response data
        $guestToken = data_get($response->json(), 'data.guest_token');
        $qty = data_get($response->json(), 'data.items.0.qty');
        $productId = data_get($response->json(), 'data.items.0.product_id');

        $this->assertNotNull($guestToken);
        $this->assertEquals(2, $qty);
        $this->assertEquals($product->id, $productId);

        // check that cookie is set
        $response->assertCookie('guest_token');

        // check that data is saved in the database
        $this->assertDatabaseHas('carts', [
            'guest_token' => $guestToken,
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'qty' => 2,
        ]);
    }

    public function test_user_cart_starts_clean_on_login()
    {
        $product = Product::firstOrFail();

        // Authorize
        $user = User::firstOrFail();
        $this->actingAs($user);

        // Add item to cart
        $response = $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'qty' => 2,
        ]);

        $response->assertStatus(201);

        // Check cart contents
        $getResponse = $this->getJson('/api/cart');
        $getResponse->assertStatus(200);

        $qty = data_get($getResponse->json(), 'data.items.0.qty');
        $this->assertEquals(2, $qty);
        
        // user's guest_token should be null
        $guestToken = data_get($getResponse->json(), 'data.guest_token');
        $this->assertNull($guestToken);
    }
}