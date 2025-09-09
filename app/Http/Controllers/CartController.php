<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;
use App\Http\Resources\CartResource;
use Illuminate\Support\Facades\Log;

/**
 * @group Cart
 *
 * Управление корзиной
 *
 * Добавление, обновление и удаление товаров в корзине (для гостя или авторизованного).
 */
class CartController extends Controller
{
    /**
     * Получить текущую корзину
     *
     * Возвращает корзину пользователя или гостя (по cookie `guest_token`).
     *
     * @cookie guest_token string Токен гостевой корзины. Example: 681e303b-c55d-4494-b1b5-14da8a5e2522
     *
     * @response 200 scenario="Успех"
     * {
     *   "data": {
     *     "id": 1,
     *     "guest_token": "681e303b-c55d-4494-b1b5-14da8a5e2522",
     *     "items": [
     *       {
     *         "id": 1,
     *         "product_id": 5,
     *         "qty": 2,
     *         "price_snapshot": "52848.00",
     *         "product": { "id": 5, "name": "Product 5" }
     *       }
     *     ]
     *   }
     * }
     *
     * @apiResourceExample {curl} Пример запроса
     * curl -X GET "http://localhost/api/cart" \
     *   -H "Accept: application/json" \
     *   --cookie "guest_token=681e303b-c55d-4494-b1b5-14da8a5e2522"
     */
    public function index(Request $request)
    {
        $cart = $this->getCart($request)->load('items.product');
        // В index/store
        $response = new CartResource($cart->load('items.product'));
        $response = $response->response()->setStatusCode($status ?? 200);

        if (!$request->user()) {
            $response->cookie('guest_token', $cart->guest_token, 60 * 24 * 30);
        } else {
            $response->withoutCookie('guest_token');
        }

        return $response;
    }
     /**
     * Добавить товар в корзину
     *
     * Если товар уже есть в корзине — увеличивает количество.  
     * Для гостя корзина определяется по cookie `guest_token`.
     *
     * @bodyParam product_id int required ID товара. Example: 5
     * @bodyParam qty int required Количество (>=1). Example: 2
     *
     * @response 201 scenario="Добавлен новый товар"
     * {
     *   "data": {
     *     "id": 1,
     *     "items": [
     *       { "id": 7, "product_id": 5, "qty": 2, "price_snapshot": "52848.00" }
     *     ]
     *   }
     * }
     * @response 200 scenario="Увеличено количество"
     * {
     *   "data": {
     *     "id": 1,
     *     "items": [
     *       { "id": 7, "product_id": 5, "qty": 4, "price_snapshot": "52848.00" }
     *     ]
     *   }
     * }
     *
     * @apiResourceExample {curl} Пример запроса
     * curl -X POST "http://localhost/api/cart" \
     *   -H "Content-Type: application/json" \
     *   -H "Accept: application/json" \
     *   -d '{"product_id":5,"qty":2}'
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty'        => 'required|integer|min:1',
        ]);

        // Determine cart
        if ($request->user()) {
            // Authorized — one cart per user_id
            $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        } else {
            // Guest — cart by guest_token
            $token = $request->cookie('guest_token') ?? Str::uuid()->toString();

            $cart = Cart::firstOrCreate(
                ['guest_token' => $token, 'user_id' => null],
                ['guest_token' => $token]
            );

            if (!$request->cookie('guest_token')) {
                cookie()->queue(cookie('guest_token', $token, 60 * 24 * 30));
            }
        }

        // Adding item to cart
        $item = $cart->items()->where('product_id', $data['product_id'])->first();

        if ($item) {
            $item->qty += $data['qty'];
            $item->save();
            $status = 200;
        } else {
            $product = Product::findOrFail($data['product_id']);
            $cart->items()->create([
                'product_id'    => $product->id,
                'qty'           => $data['qty'],
                'price_snapshot'=> $product->price_minor,
            ]);
            $status = 201;
        }

        Log::info('CartController@store:result', [
            'cart_id'     => $cart->id,
            'user_id'     => $cart->user_id,
            'guest_token' => $cart->guest_token,
            'items_qty'   => $cart->items()->sum('qty'),
        ]);


        // В index/store
        $response = new CartResource($cart->load('items.product'));
        $response = $response->response()->setStatusCode($status ?? 200);

        if (!$request->user()) {
            $response->cookie('guest_token', $cart->guest_token, 60 * 24 * 30);
        } else {
            $response->withoutCookie('guest_token');
        }
        return $response->setStatusCode($status);
    }

    /**
     * Обновить количество товара
     *
     * @urlParam id int required ID позиции корзины. Example: 1
     * @bodyParam qty int required Новое количество (>=1). Example: 3
     *
     * @response 200 scenario="Успех"
     * {
     *   "data": {
     *     "id": 1,
     *     "items": [
     *       { "id": 7, "product_id": 5, "qty": 3, "price_snapshot": "52848.00" }
     *     ]
     *   }
     * }
     *
     * @apiResourceExample {curl} Пример запроса
     * curl -X PUT "http://localhost/api/cart/7" \
     *   -H "Content-Type: application/json" \
     *   -d '{"qty":3}'
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $cart = $this->getCart($request);
        $item = $cart->items()->where('id', $id)->firstOrFail();
        $item->qty = $data['qty'];
        $item->save();

        return new CartResource($cart->load('items.product'));
    }
     /**
     * Удалить товар из корзины
     *
     * @urlParam id int required ID позиции корзины. Example: 1
     *
     * @response 200 scenario="Успех"
     * {
     *   "data": {
     *     "id": 1,
     *     "items": []
     *   }
     * }
     *
     * @apiResourceExample {curl} Пример запроса
     * curl -X DELETE "http://localhost/api/cart/7" \
     *   -H "Accept: application/json"
     */
    public function destroy(Request $request, $id)
    {
        $cart = $this->getCart($request);
        $item = $cart->items()->where('id', $id)->firstOrFail();
        $item->delete();

        return new CartResource($cart->load('items.product'));
    }


    /** @internal */
    protected function getCart(Request $request)
    {
        if ($request->user()) {
            $user = $request->user();

            // Search for existing user cart
            $cart = Cart::where('user_id', $user->id)->first();

            // If user had a guest cart, merge it
            $guestToken = $request->cookie('guest_token');
            if ($guestToken) {
                $guestCart = Cart::where('guest_token', $guestToken)
                                ->whereNull('user_id')
                                ->first();

                if ($guestCart && $guestCart->items()->count() > 0) {
                    if ($cart) {
                        // Merging
                        foreach ($guestCart->items as $item) {
                            $existing = $cart->items()->where('product_id', $item->product_id)->first();
                            if ($existing) {
                                $existing->qty += $item->qty;
                                $existing->save();
                            } else {
                                $cart->items()->create([
                                    'product_id'     => $item->product_id,
                                    'qty'            => $item->qty,
                                    'price_snapshot' => $item->price_snapshot,
                                ]);
                            }
                        }
                        $guestCart->delete();
                    } else {
                        // Merge guest cart into user cart
                        $guestCart->update([
                            'user_id'     => $user->id,
                            'guest_token' => null,
                        ]);
                        $cart = $guestCart;
                    }

                    cookie()->queue(cookie()->forget('guest_token'));
                }
            }

            if (!$cart) {
                $cart = Cart::create([
                    'user_id'     => $user->id,
                    'guest_token' => null,
                ]);
            }

            return $cart->fresh('items.product');
        }

        // --- Guest ---
        $token = $request->cookie('guest_token') ?? Str::uuid()->toString();

        $cart = Cart::firstOrCreate(
            ['guest_token' => $token, 'user_id' => null],
            ['guest_token' => $token]
        );

        if (!$request->user() && !$request->cookie('guest_token')) {
            cookie()->queue(cookie('guest_token', $token, 60 * 24 * 30));
        }

        return $cart->fresh('items.product');
    }

}
