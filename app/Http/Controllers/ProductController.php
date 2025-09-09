<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use App\Models\Attribute;

/**
 * @group Products
 *
 * Каталог товаров
 *
 * Эндпоинты для получения списка и карточки товара, с фильтрами и сортировкой.
 */
class ProductController extends Controller
{
    /**
     * Список товаров (с фильтрами и сортировкой)
     *
     * Возвращает пагинированный список товаров. Поддерживает фильтрацию по категориям,
     * диапазону цены и EAV-атрибутам.
     *
     * @queryParam page integer Страница пагинации. Example: [1,2]
     * @queryParam category_ids[] int[] ID категорий (можно несколько). Example: [5,12]
     * @queryParam price_min integer Минимальная цена (в minor-единицах). Example: 1000
     * @queryParam price_max integer Максимальная цена (в minor-единицах). Example: 100000
     * @queryParam sort string Сортировка: price_asc, price_desc. По умолчанию — последние. Example: price_desc
     * @queryParam attr[color] string Фильтр по строковому атрибуту. Example: red
     * @queryParam attr[size][] string[] Фильтр по множественным значениям. Example: M
     * @queryParam attr[width] int Фильтр по числовому атрибуту. Example: 42
     *
     * @response 200 scenario="Успех (пагинировано)"
     * {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Product 1",
     *       "slug": "product-1",
     *       "price": 52848,
     *       "category": {
     *         "id": 12,
     *         "name": "Кроссовки",
     *         "slug": "obuv-muzhskaya-krossovki"
     *       },
     *       "attributes": [
     *         { "attribute_id": 1, "value_string": "red" },
     *         { "attribute_id": 2, "value_string": "M" },
     *         { "attribute_id": 3, "value_int": 42 }
     *       ]
     *     }
     *   ],
     *   "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
     *   "meta": { "current_page": 1, "last_page": 5, "per_page": 10, "total": 50 }
     * }
     *
     * @apiResourceExample {curl} Пример: базовый список
     * curl -X GET "http://localhost/api/products?page=1" \
     *   -H "Accept: application/json"
     *
     * @apiResourceExample {curl} Пример: фильтр по категории и цене
     * curl -X GET "http://localhost/api/products?category_ids[]=5&price_min=1000&price_max=100000&sort=price_desc" \
     *   -H "Accept: application/json"
     *
     * @apiResourceExample {curl} Пример: фильтр по EAV-атрибутам
     * curl -G "http://localhost/api/products" \
     *   -H "Accept: application/json" \
     *   --data-urlencode "attr[color]=red" \
     *   --data-urlencode "attr[size][]=M" \
     *   --data-urlencode "attr[width]=42"
     */
    // List of products with optional filtering by category and attributes
    public function index(Request $request)
    {   
        $query = Product::with(['category', 'attributes']); 
        
        // Filter by category IDs
        if ($request->has('category_ids')) {
            $query->whereIn('category_id', $request->input('category_ids'));
        }

        // Filter by price range
        if ($request->filled('price_min')) {
            $query->where('price_minor', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price_minor', '<=', $request->input('price_max'));
        }

        // Filter by EAV attributes
        if ($request->has('attr')) {
            foreach ($request->input('attr') as $code => $value) {
                // check that the attribute really exists
                $attribute = Attribute::where('code', $code)->first();
                if (!$attribute) {
                    abort(400, "Invalid attribute: $code");
                }

                $query->whereHas('attributes', function ($q) use ($attribute, $value) {
                    $q->where('attribute_id', $attribute->id);

                    // selecting the right column based on attribute type
                    $column = match ($attribute->type) {
                        'string'  => 'value_string',
                        'integer' => 'value_int',
                        'decimal' => 'value_decimal',
                        'bool'    => 'value_boolean',
                        default   => 'value_string', // fallback
                    };

                    if (is_array($value)) {
                        $q->whereIn($column, $value);
                    } else {
                        $q->where($column, $value);
                    }
                });
            }
        }


        // Sorting
        if ($request->input('sort') === 'price_asc') {
            $query->orderBy('price_minor', 'asc');
        } elseif ($request->input('sort') === 'price_desc') {
            $query->orderBy('price_minor', 'desc');
        } else {
            $query->latest(); 
        }

        // Pagination
        $products = $query-> paginate(10);
        
        return ProductResource::collection($products);
    }
        /**
     * Карточка товара
     *
     * Возвращает один товар по `slug` с категорией и атрибутами.
     *
     * @urlParam slug string required Slug товара. Example: product-1
     *
     * @response 200 scenario="Успех"
     * {
     *   "data": {
     *     "id": 1,
     *     "name": "Product 1",
     *     "slug": "product-1",
     *     "price": 52848,
     *     "category": { "id": 12, "name": "Кроссовки", "slug": "obuv-muzhskaya-krossovki" },
     *     "attributes": [
     *       { "attribute_id": 1, "value_string": "red" },
     *       { "attribute_id": 2, "value_string": "M" },
     *       { "attribute_id": 3, "value_int": 42 }
     *     ]
     *   }
     * }
     *
     * @apiResourceExample {curl} Пример запроса
     * curl -X GET "http://localhost/api/products/product-1" \
     *   -H "Accept: application/json"
     */
    public function show($slug)
    {
        $product = Product::with(['category', 'attributes'])->where('slug', $slug)->firstOrFail();

        return new ProductResource($product);

    }
}
