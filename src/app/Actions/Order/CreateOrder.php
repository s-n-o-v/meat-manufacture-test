<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\Product;
use App\DTOs\Order\CreateOrderData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    public function handle(CreateOrderData $data): Order
    {
        return DB::transaction(function () use ($data) {
            $totalPrice = 0;
            $productsToAttach = [];

            foreach ($data->products as $item) {
                $product = Product::findOrFail($item['id']);
                $qty = $item['qty'];

                if ($qty > $product->qty) {
                    throw ValidationException::withMessages([
                        'products' => ["Недостаточно на складе: {$product->name}"],
                    ]);
                }

                $total = $product->price * $qty;
                $totalPrice += $total;

                $productsToAttach[$product->id] = [
                    'price' => $product->price,
                    'qty' => $qty,
                ];
            }

            $order = Order::create([
                'user_id' => $data->user_id,
                'status_id' => 1, // Статус по умолчанию
                'total_price' => $totalPrice,
                'comment' => $data->comment,
            ]);

            $order->products()->attach($productsToAttach);

            return $order;
        });
    }
}
