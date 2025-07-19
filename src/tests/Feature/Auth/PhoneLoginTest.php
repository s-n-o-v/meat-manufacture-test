<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PhoneLoginTest extends TestCase
{
    use DatabaseTransactions;
    const admin_Phone = '+79999999999'; // Требуется валидный номер телефона из БД
    /** @test */
    public function it_requests_verification_code_for_valid_phone()
    {
        // Подменим Notification, чтобы не слать реальные SMS
        Notification::fake();

        $phone = self::admin_Phone;

        $response = $this->postJson('/api/login/phone/request-code', [
            'phone' => $phone,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Код отправлен',
        ]);
    }

    /** @test */
    public function it_returns_validation_error_for_invalid_phone()
    {
        $response = $this->postJson('/api/login/phone/request-code', [
            'phone' => 'not-a-phone-number',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_rejects_invalid_verification_code()
    {
        $phone = '+79001234567';
        $validCode = '123456';
        $wrongCode = '999999';

        cache()->put("phone_login_code:$phone", $validCode, now()->addMinutes(5));

        $response = $this->postJson('/api/login/phone/verify-code', [
            'phone' => $phone,
            'code' => $wrongCode,
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Неверный или просроченный код',
        ]);
    }

    /** @test */
    public function it_requires_code_and_phone_fields()
    {
        $response = $this->postJson('/api/login/phone/verify-code', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone', 'code']);
    }
}
