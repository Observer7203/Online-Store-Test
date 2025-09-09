<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attribute;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * @group Attributes
 *
 * Управление атрибутами товаров
 *
 * Атрибуты (EAV) используются для описания характеристик товаров
 * (например: цвет, размер, ширина и т.д.).
 */
class AttributeController extends Controller
{

        /**
     * Получить список атрибутов
     *
     * Возвращает полный список всех атрибутов.
     *
     * @response 200 scenario="Успешный запрос"
     * [
     *   {
     *     "id": 1,
     *     "name": "Цвет",
     *     "code": "color",
     *     "type": "string",
     *     "created_at": "2025-09-09T12:30:00.000000Z",
     *     "updated_at": "2025-09-09T12:30:00.000000Z"
     *   }
     * ]
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @apiResourceExample {curl} Пример запроса:
     * curl -X GET http://localhost/api/attributes \
     *   -H "Accept: application/json"
     */
    public function index()
    {
        $attributes=Attribute::all();
        return response()->json($attributes);
    }
    /**
     * Создать новый атрибут
     *
     * Создаёт атрибут с указанными полями.
     *
     * @bodyParam name string required Название атрибута. Example: Цвет
     * @bodyParam code string required Уникальный код атрибута (slug). Example: color
     * @bodyParam type string required Тип атрибута. Допустимые значения: int, decimal, bool, string. Example: string
     *
     * @response 201 scenario="Успешное создание"
     * {
     *   "id": 2,
     *   "name": "Размер",
     *   "code": "size",
     *   "type": "string",
     *   "created_at": "2025-09-09T12:45:00.000000Z",
     *   "updated_at": "2025-09-09T12:45:00.000000Z"
     * }
     * @response 422 scenario="Ошибка валидации"
     * {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "code": ["The code has already been taken."]
     *   }
     * }
     *
     * @apiResourceExample {curl} Пример запроса:
     * curl -X POST http://localhost/api/attributes \
     *   -H "Content-Type: application/json" \
     *   -H "Accept: application/json" \
     *   -d '{"name":"Размер","code":"size","type":"string"}'
     */
    public function store(Request $request)
    {
        $data=$request->validate([
            'name'=>'required|string|max:255',
            'code'=>'required|string|max:255|unique:attributes,code',
            'type'=>['required', Rule::in(['int','decimal','bool','string'])],
        ]);

        $attribute=Attribute::create($data);

        response()->json($attribute,201);
    }   
    /**
     * Удалить атрибут
     *
     * Удаляет атрибут по ID.
     *
     * @urlParam id integer required ID атрибута. Example: 1
     *
     * @response 200 scenario="Успешное удаление"
     * {
     *   "message": "Attribute deleted"
     * }
     * @response 404 scenario="Атрибут не найден"
     * {
     *   "message": "Attribute not found"
     * }
     *
     * @apiResourceExample {curl} Пример запроса:
     * curl -X DELETE http://localhost/api/attributes/1 \
     *   -H "Accept: application/json"
     */
    public function destroy($id)
    {
        $attribute=Attribute::find($id);
        if($attribute)
        {
            $attribute->delete();
            return response()->json(['message'=>'Attribute deleted'],200);
        }
        else
        {
            return response()->json(['message'=>'Attribute not found'],404);
        }
    }
}
