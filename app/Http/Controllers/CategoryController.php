<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;


    /**
 * @group Categories
 *
 * Категории каталога
 *
 * Дерево категорий (с вложенными children).
 */
class CategoryController extends Controller
{
        /**
     * Дерево категорий
     *
     * Возвращает список корневых категорий (`depth = 1`) с вложенными подкатегориями.
     *
     * @response 200 scenario="Успех"
     * {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Одежда",
     *       "slug": "odezhda",
     *       "children": [
     *         {
     *           "id": 2,
     *           "name": "Мужская",
     *           "slug": "odezhda-muzhskaya",
     *           "children": [
     *             { "id": 3, "name": "Куртки", "slug": "odezhda-muzhskaya-kurtki" }
     *           ]
     *         }
     *       ]
     *     }
     *   ]
     * }
     *
     * @apiResourceExample {curl} Пример запроса
     * curl -X GET "http://localhost/api/categories/tree" \
     *   -H "Accept: application/json"
     */
    public function tree()
    {
        $categories = Category::with('children.children')->where('depth', 1)->get();
        return CategoryResource::collection($categories);
    }
}
