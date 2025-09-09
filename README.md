<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Online Store API

Тестовое задание: backend часть интернет-магазина на Laravel.

---

##  Запуск проекта

Часть 1. Docker-сборка и запуск проекта
## 1. Docker сборка

В проекте используется Laravel + Docker (php-fpm, nginx, postgres).

Базовая структура взята из [Laravel Docker Examples](https://github.com/rw4lll/laravel-docker-examples) и [официального гайда](https://docs.docker.com/guides/frameworks/laravel/development-setup/).

### Запуск проекта

1. Собрать и запустить контейнеры:
   ```bash
   docker-compose up -d --build


Установить зависимости:

docker-compose exec php-fpm composer install


Выполнить миграции и сидеры:

docker-compose exec php-fpm php artisan migrate:fresh --seed


Запустить тесты:

docker-compose exec php-fpm php artisan test


Документация API (Scribe) будет доступна по:

http://localhost/docs


Часть 2. Sanctum и Scribe
## 2. Установка Sanctum и Scribe

### Sanctum (аутентификация)

1. Устанавливаем пакет:
   ```bash
   docker run --rm -v $(pwd):/app composer require laravel/sanctum


Публикуем миграции и конфиг:

docker-compose exec php-fpm php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"


Применяем миграции:

docker-compose exec php-fpm php artisan migrate

Scribe (документация API)

Устанавливаем пакет:

docker-compose exec php-fpm composer require knuckleswtf/scribe --dev


Публикуем конфиги и шаблоны:

docker-compose exec php-fpm php artisan vendor:publish --tag=scribe-config
docker-compose exec php-fpm php artisan vendor:publish --tag=scribe-views


Генерируем документацию:

docker-compose exec php-fpm php artisan scribe:generate


Документация будет доступна по адресу:

http://localhost/docs



---

### Часть 3. Makefile команды
(чтобы не писать длинные docker-команды руками)

```makefile
# Makefile

up:
	docker-compose up -d --build

down:
	docker-compose down

bash:
	docker-compose exec php-fpm bash

migrate:
	docker-compose exec php-fpm php artisan migrate

fresh:
	docker-compose exec php-fpm php artisan migrate:fresh --seed

seed:
	docker-compose exec php-fpm php artisan db:seed

test:
	docker-compose exec php-fpm php artisan test

make-model:
	docker-compose exec php-fpm php artisan make:model $(name) -mcr

make-controller:
	docker-compose exec php-fpm php artisan make:controller $(name)Controller --api

make-seeder:
	docker-compose exec php-fpm php artisan make:seeder $(name)Seeder
```
```
MakeFile Команды

Миграции
docker-compose exec php-fpm php artisan make:migration create_categories_table
docker-compose exec php-fpm php artisan make:migration create_products_table
docker-compose exec php-fpm php artisan make:migration create_attributes_table
docker-compose exec php-fpm php artisan make:migration create_product_attributes_table
docker-compose exec php-fpm php artisan make:migration create_carts_table
docker-compose exec php-fpm php artisan make:migration create_cart_items_table
docker-compose exec php-fpm php artisan make:migration create_orders_table
docker-compose exec php-fpm php artisan make:migration create_order_items_table

Модели
docker-compose exec php-fpm php artisan make:model User
docker-compose exec php-fpm php artisan make:model Category
docker-compose exec php-fpm php artisan make:model Product
docker-compose exec php-fpm php artisan make:model Attribute
docker-compose exec php-fpm php artisan make:model ProductAttribute
docker-compose exec php-fpm php artisan make:model Cart
docker-compose exec php-fpm php artisan make:model CartItem
docker-compose exec php-fpm php artisan make:model Order
docker-compose exec php-fpm php artisan make:model OrderItem

Контроллеры
docker-compose exec php-fpm php artisan make:controller AuthController
docker-compose exec php-fpm php artisan make:controller CategoryController
docker-compose exec php-fpm php artisan make:controller ProductController
docker-compose exec php-fpm php artisan make:controller AttributeController
docker-compose exec php-fpm php artisan make:controller CartController
docker-compose exec php-fpm php artisan make:controller OrderController

Ресурсы
docker-compose exec php-fpm php artisan make:resource CategoryResource
docker-compose exec php-fpm php artisan make:resource ProductResource
docker-compose exec php-fpm php artisan make:resource CartResource
docker-compose exec php-fpm php artisan make:resource OrderResource

Сидеры
docker-compose exec php-fpm php artisan make:seeder UserSeeder
docker-compose exec php-fpm php artisan make:seeder CategorySeeder
docker-compose exec php-fpm php artisan make:seeder ProductSeeder

Тесты (Feature & Unit)
docker-compose exec php-fpm php artisan make:test AuthTest
docker-compose exec php-fpm php artisan make:test CartTest
docker-compose exec php-fpm php artisan make:test OrderTest
docker-compose exec php-fpm php artisan make:test ProductTest
docker-compose exec php-fpm php artisan make:test EavFilterTest --unit

### Часть 4. Архитектура БД (из проекта)
```
```

categories: трёхуровневое дерево категорий (depth 1–3).

products: товары (id, category_id, name, slug, description, price_minor).

attributes: EAV-атрибуты (код, название, тип).

product_attributes: значения атрибутов (int, decimal, string, bool).

carts: корзины (для гостя по guest_token, для пользователя по user_id).

cart_items: позиции корзины (product_id, qty, price_snapshot).

orders: заказы (user_id или guest_token, email, phone, status, total_minor, idempotency_key).

order_items: позиции заказа (product_id, name_snapshot, price_snapshot, qty).
```
###Часть 5. Тесты 

```

CartTest – гость/пользователь, слияние корзин.

OrderTest – создание заказа (гость, пользователь), просмотр заказов.

EavFilterTest – фильтрация товаров по атрибутам.

AuthTest - тест Авторизации

ProductTest - тест Продуктов


Запуск тестов:

docker-compose exec php-fpm php artisan test


 Цены в minor units
Все цены хранятся в целых числах (price_minor = сумма в тыйинах/центах).
Например:

52848 → 528.48 ₸

1999 → 19.99 ₸

исключает ошибки округления при работе с float.

 Аутентификация
Используется Laravel Sanctum.

При регистрации/логине возвращается токен:

json

{
  "access_token": "3|fxCBcmuswMoPPVo5kr5WR5VXMZUT8JnoGszgfIn89b805eda",
  "token_type": "Bearer"
}
Передавать в заголовке:

makefile

Authorization: Bearer <SANCTUM_TOKEN>
 Корзина
GET /api/cart – получить корзину

POST /api/cart – добавить товар

PUT /api/cart/{id} – обновить количество

DELETE /api/cart/{id} – удалить товар

 Для гостей используется cookie guest_token.
 При авторизации корзина гостя объединяется с корзиной пользователя.

 Заказы
POST /api/orders – создать заказ (гость или пользователь)

GET /api/orders – список заказов (только для авторизованных)

Поля заказа:

email

phone

status (enum: placed, paid, cancelled)

total_minor

idempotency_key (для защиты от дублей)




---



