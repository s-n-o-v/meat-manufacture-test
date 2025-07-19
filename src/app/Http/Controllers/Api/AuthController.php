<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Notifications\PhoneLoginCodeNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Создание нового пользователя
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        Log::info('Register user');
        $data = $request->validated();
        Log::info('Validated');

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'password' => Hash::make($data['password']),
        ]);

//        $user->notify(new WelcomeNotification());

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
//            'user' => $user,
        ], 201);
    }

    /**
     * Универсальный метод для авторизации по логину и паролю
     * В качестве логина может быть email или телефон
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $fieldType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($fieldType, $request->login)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid user credentials'], 401);
        }

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
            'user' => $user,
        ]);
    }

    /**
     * Авторизация по email + пароль
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function loginByEmail(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    /**
     * Авторизация по телефону + пароль
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function loginByPhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required'],
            'password' => ['required'],
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid phone or password'], 401);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    /**
     * Отправка SMS с кодом на указанный номер
     *
     * @param Request $request
     * @param SmsService $sms
     * @return JsonResponse
     */
    public function requestPhoneCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
        ]);

        $phone = $request->phone;
        $code = rand(100000, 999999); // 6-значный код

        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден'], 404);
        }

        $user->notify(new PhoneLoginCodeNotification($code));

        // Кешируем код на 5 минут
        Cache::put("login_code:{$phone}", $code, now()->addMinutes(5));

        return response()->json(['message' => 'Код отправлен']);
    }

    /**
     * Валидация пользовательского телефона через SMS код
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyPhoneCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $cachedCode = Cache::get("login_code:{$request->phone}");
        Log::info("Cached code for {$request->phone}: {$cachedCode}");
        if (!$cachedCode || $cachedCode != $request->code) {
            return response()->json(['message' => 'Неверный или просроченный код'], 401);
        }

        // Удаляем код, чтобы нельзя было повторно использовать
        Cache::forget("login_code:{$request->phone}");

        // Ищем пользователя по телефону
        $user = User::firstOrCreate(
            ['phone' => $request->phone],
            ['name' => 'Пользователь', 'email' => Str::uuid() . '@placeholder.local', 'password' => Hash::make(Str::random(16))]
        );

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    /**
     * Завершает пользовательский сеанс и удаляет токен
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
