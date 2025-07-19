<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_basic_route_is_accessible()
    {
        $response = $this->postJson('/api/login/phone/request-code', [
            'phone' => "+79999999999",
        ]);

        $response->assertStatus(200);
    }
}
