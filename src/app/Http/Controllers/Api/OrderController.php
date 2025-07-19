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

    /**
     * Получить заказы пользователя
     *
     * @param OrdersGetRequest $request
     * @return AnonymousResourceCollection
     *
     * @OA\Get(
     *     path="/api/orders",
     *     tags={"Orders"},
     *     summary="Получить заказы по user_id (только для админа)",
     *     description="Возвращает список заказов пользователя по переданному user_id. Требует авторизации и прав администратора.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="ID пользователя, чьи заказы нужно получить",
     *         required=true,
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список заказов пользователя",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/OrderResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Нет прав для просмотра заказов других пользователей"
     *     )
     * )
     */
    public function userOrders(OrdersGetRequest $request): AnonymousResourceCollection
    {
        $orders = Order::where('user_id', $request->user_id)->with('products', 'user', 'status')->get();
        return OrderResource::collection($orders);
    }

    // Новый заказ

    /**
     * Создать заказ
     *
     * @param StoreOrderRequest $request
     * @param CreateOrder $action
     * @return OrderResource
     *
     * @OA\Post(
     *     path="/api/orders",
     *     tags={"Orders"},
     *     summary="Создать заказ",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"products"},
     *             @OA\Property(
     *                 property="products",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="qty", type="integer", example=2)
     *                 )
     *             ),
     *             @OA\Property(property="comment", type="string", example="Пожалуйста, доставьте после 18:00")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Заказ успешно создан"),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован"
     *     ),
     * )
     */
    public function store(StoreOrderRequest $request, CreateOrder $action): OrderResource
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
