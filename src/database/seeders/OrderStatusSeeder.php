<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;
use App\Enums\OrderStatusEnum;
use Illuminate\Support\Facades\DB;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (OrderStatusEnum::cases() as $case) {
            DB::table('order_statuses')->updateOrInsert(
                ['id' => $case->value],
                ['name' => $case->label()]
            );
        }
    }
}
