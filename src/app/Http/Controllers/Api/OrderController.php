<?php

namespace App\Http\Controllers\Api;

use App\Actions\Order\CreateOrder;
use App\DTOs\Order\CreateOrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrdersGetRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    // Админ: Все заказы
    public function index(): AnonymousResourceCollection
    {
        $orders = Order::with('products', 'user', 'status')->get();
        return OrderResource::collection($orders);
    }

    // Заказы пользователя
    public function userOrders(OrdersGetRequest $request): AnonymousResourceCollection
    {
        $orders = Order::where('user_id', $request->user_id)->with('products', 'user', 'status')->get();
        return OrderResource::collection($orders);
    }

    // Новый заказ
    public function store(StoreOrderRequest $request, CreateOrder $action)
    {
        $dto = new CreateOrderData(
            user_id: $request->input('user_id'),
            products: $request->input('products'),
            comment: $request->input('comment')
        );

        $order = $action->handle($dto);

        return new OrderResource($order->load('products', 'status'));
    }
}
