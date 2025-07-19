<?php

namespace App\DTOs\Order;

class CreateOrderData
{
    public function __construct(
        public int $user_id,
        public array $products,
        public ?string $comment = null,
    ) {}
}
