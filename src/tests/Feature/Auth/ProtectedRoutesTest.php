<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProtectedRoutesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_can_access_protected_route()
    {
        // Создаём пользователя
        $user = User::factory()->create();

        // Авторизуем его через Sanctum
        Sanctum::actingAs($user);

        // Делаем запрос к защищённому маршруту (например, /api/orders)
        $response = $this->getJson('/api/orders');

        $response->assertStatus(200); // или 204 / 201 — в зависимости от реализации
    }

    public function test_unauthenticated_user_gets_unauthorized()
    {
        // Без авторизации
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthorized',
        ]);
    }
}
