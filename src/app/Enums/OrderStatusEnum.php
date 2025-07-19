<?php

namespace App\Enums;

enum OrderStatusEnum: int
{
    case Pending = 1;
    case Paid = 2;
    case Completed = 3;
    case Cancelled = 4;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает оплаты',
            self::Paid => 'Оплачен',
            self::Completed => 'Доставлен',
            self::Cancelled => 'Отменён',
        };
    }
}
