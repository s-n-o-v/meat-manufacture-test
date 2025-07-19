<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Le admin',
            'phone' => '+79999999999',
            'address' => 'Минская улица, 120/7',
            'email' => 'admin@meatmanufacture.zero',
        ]);

        // Категории и продукты
        Category::factory()
            ->count(5)
            ->has(Product::factory()->count(10))
            ->create();

        // Пользователи еще 5 для тестироования
        User::factory()->count(5)->create();

        $this->call(OrderStatusSeeder::class);

        // Заказы
        $users = User::all();
        $products = Product::all();

        foreach ($users as $user) {
            Order::factory()
                ->count(2)
                ->create(['user_id' => $user->id])
                ->each(function ($order) use ($products) {
                    $selected = $products->random(rand(1, 5));
                    $total = 0;

                    foreach ($selected as $product) {
                        $price = $product->price;
                        $order->products()->attach($product->id, ['price' => $price]);
                        $total += $price;
                    }

                    $order->update(['total_price' => $total]);
                });
        }
    }
}
