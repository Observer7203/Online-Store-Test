<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\OrderResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * @group Orders
 *
 * Управление заказами
 *
 * Создание заказа из корзины (гость или пользователь) и список заказов пользователя.
 */
class OrderController extends Controller
{

     /**
     * Создать заказ (из текущей корзины)
     *
     * Для гостя используется `guest_token` (cookie или параметр запроса), для авторизованного — `user_id`.
     *
     * @bodyParam email string required Email заказчика. Example: guest@example.com
     * @bodyParam phone string required Телефон заказчика. Example: +77001112233
     *
     * @header Idempotency-Key string Example: a1b2c3d4 Уникальный ключ для предотвращения дублирования заказа.
     * @cookie guest_token string Токен гостевой корзины (для гостя). Example: 681e303b-c55d-4494-b1b5-14da8a5e2522
     *
     * @response 201 scenario="Успешно создан"
     * {
     *   "data": {
     *     "id": 3,
     *     "user_id": null,
     *     "guest_token": "681e303b-c55d-4494-b1b5-14da8a5e2522",
     *     "email": "guest@example.com",
     *     "phone": "+77001112233",
     *     "status": "placed",
     *     "total_minor": 105696,
     *     "idempotency_key": "a1b2c3d4",
     *     "items": [
     *       {
     *         "id": 7,
     *         "product_id": 1,
     *         "name_snapshot": "Product 1",
     *         "price_snapshot": "52848.00",
     *         "qty": 2
     *       }
     *     ]
     *   }
     * }
     * @response 200 scenario="Повтор с тем же Idempotency-Key"
     * { "message": "Duplicate order" }
     * @response 400 scenario="Пустая корзина"
     * { "message": "Cart is empty" }
     * @response 500 scenario="Ошибка сервера"
     * { "error": "Order creation failed" }
     *
     * @apiResourceExample {curl} Пример: гость (cookie)
     * curl -X POST "http://localhost/api/orders" \
     *   -H "Content-Type: application/json" \
     *   -H "Accept: application/json" \
     *   -H "Idempotency-Key: a1b2c3d4" \
     *   --cookie "guest_token=681e303b-c55d-4494-b1b5-14da8a5e2522" \
     *   -d '{"email":"guest@example.com","phone":"+77001112233"}'
     *
     * @apiResourceExample {curl} Пример: гость (query param вместо cookie)
     * curl -X POST "http://localhost/api/orders?guest_token=681e303b-c55d-4494-b1b5-14da8a5e2522" \
     *   -H "Content-Type: application/json" \
     *   -H "Accept: application/json" \
     *   -H "Idempotency-Key: a1b2c3d4" \
     *   -d '{"email":"guest@example.com","phone":"+77001112233"}'
     */
    // Create Order
        public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
        ]);

        // Idempotency key to prevent duplicate orders
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey && Order::where('idempotency_key', $idempotencyKey)->exists()) {
            Log::warning('Duplicate order prevented', [
                'idempotency_key' => $idempotencyKey,
                'email' => $data['email'],
            ]);
            return response()->json(['message' => 'Duplicate order'], 200);
        }

        $cart = $this->getCart($request);

        if ($cart->items->isEmpty()) {
            Log::warning('Order attempt with empty cart', [
                'email' => $data['email'],
                'cart_id' => $cart->id,
            ]);
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id'        => $request->user()?->id,
                'guest_token'    => $request->user() ? null : $cart->guest_token, // для гостей
                'email'          => $data['email'],
                'phone'          => $data['phone'],
                'total_minor'    => round(
                    $cart->items->sum(fn($i) => $i->qty * $i->price_snapshot),
                    2
                ),
                'status'         => 'placed',
                'idempotency_key'=> $idempotencyKey,
            ]);


            Log::info('Order created', [
                'order_id' => $order->id,
                'user_id'  => $order->user_id,
                'email'    => $order->email,
                'phone'    => $order->phone,
                'total'    => $order->total_minor,
                'cart_id'  => $cart->id,
                'items'    => $cart->items->count(),
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'       => $order->id,
                    'product_id'     => $item->product_id,
                    'name_snapshot'  => $item->product->name,
                    'price_snapshot' => $item->price_snapshot,
                    'qty'            => $item->qty,
                ]);
                Log::info('OrderItem created', [
                    'order_id'   => $order->id,
                    'product_id' => $item->product_id,
                    'qty'        => $item->qty,
                    'price'      => $item->price_snapshot,
                ]);
            }

            $cart->items()->delete();
            Log::info('Cart cleared after order', ['cart_id' => $cart->id]);

            DB::commit();

            return (new OrderResource($order->load('items')))
                ->response()
                ->setStatusCode(201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order creation failed', [
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error'   => 'Order creation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Список заказов текущего пользователя
     *
     * @authenticated
     *
     * @response 200 scenario="Успех"
     * {
     *   "data": [
     *     {
     *       "id": 10,
     *       "user_id": 1,
     *       "guest_token": null,
     *       "email": "user@example.com",
     *       "phone": "+77001112233",
     *       "status": "placed",
     *       "total_minor": 18356,
     *       "items": [
     *         {
     *           "id": 21,
     *           "product_id": 1,
     *           "name_snapshot": "Product 1",
     *           "price_snapshot": "18356.00",
     *           "qty": 1
     *         }
     *       ]
     *     }
     *   ]
     * }
     * @response 401 scenario="Не авторизован"
     * { "message": "Unauthorized" }
     *
     * @apiResourceExample {curl} Пример запроса
     * curl -X GET "http://localhost/api/orders" \
     *   -H "Accept: application/json" \
     *   -H "Authorization: Bearer <SANCTUM_TOKEN>"
     */
    // List Orders for Authenticated User
    public function index(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orders = Order::where('user_id', $request->user()->id)
            ->with('items')
            ->get();

        return OrderResource::collection($orders);
    }

    /** @internal Получение корзины (гость/пользователь) */
   protected function getCart(Request $request)
    {
    if ($request->user()) {
        // Search cart by user_id
        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id'     => $request->user()->id,
                'guest_token' => null,
            ]);
        }

        return $cart;
    }

    // for guests — try cookie or guest_token param
    $token = $request->cookie('guest_token') ?? $request->input('guest_token');

    if ($token) {
        $cart = Cart::where('guest_token', $token)
                    ->whereNull('user_id')
                    ->first();
    } else {
        $token = Str::uuid()->toString();
        $cart = null;
    }

    if (!$cart) {
        $cart = Cart::create([
            'guest_token' => $token,
            'user_id'     => null,
        ]);
    }

    // if no guest_token cookie, set it
    if (!$request->cookie('guest_token')) {
        cookie()->queue(cookie('guest_token', $token, 60 * 24 * 30));
    }

    Log::info('getCart:guest', [
        'incoming_cookie' => $request->cookie('guest_token'),
        'resolved_token'  => $token,
        'cart_id'         => $cart->id,
        'items_qty'       => $cart->items()->sum('qty'),
    ]);

    return $cart;
}

}
