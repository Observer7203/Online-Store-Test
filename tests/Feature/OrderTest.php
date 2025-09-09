<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    public function test_guest_can_create_order()
    {
        $product = Product::firstOrFail();

        // Guest adds item to cart
        $responseCart = $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'qty' => 2,
        ])->assertStatus(201);

        // take guest_token from response
        $guestToken = data_get($responseCart->json(), 'data.guest_token');
        $this->assertNotNull($guestToken, 'guest_token отсутствует в CartResource');

        // Creating order with cookie
        $response = $this->withCookie('guest_token', $guestToken)->postJson('/api/orders', [
            'email' => 'guest@example.com',
            'phone' => '+77001112233',
            'guest_token' => $guestToken,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', ['email' => 'guest@example.com']);
    }

    public function test_user_can_create_order()
    {
        $user = User::firstOrFail();
        $product = Product::firstOrFail();

        // Authenticate as user
        $this->actingAs($user);

        // Add to cart
        $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'qty' => 1,
        ])->assertStatus(201);

        // Create order
        $response = $this->postJson('/api/orders', [
            'email' => $user->email,
            'phone' => '+77001112233',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
    }

    public function test_user_can_view_his_orders()
    {
        $user = User::firstOrFail();
        $product = Product::firstOrFail();

        // Authenticate as user
        $this->actingAs($user);

        // add to cart
        $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'qty' => 1,
        ])->assertStatus(201);

        // Create order
        $this->postJson('/api/orders', [
            'email' => $user->email,
            'phone' => '+77001112233',
        ])->assertStatus(201);

        // Show orders
        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'user_id',
                        'email',
                        'phone',
                        'status',
                        'items' => [
                            [
                                'id',
                                'product_id',
                                'name_snapshot',
                                'price_snapshot',
                                'qty'
                            ]
                        ]
                    ]
                ]
            ]);

        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
    }
}
