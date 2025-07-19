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
use Illuminate\Support\Str;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Meat Manufacture API. !Test project",
 *     description="Документация API проекта"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer"
 * )
 */
class AuthController extends Controller
{
    /**
     * Создание нового пользователя
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Регистрация пользователя",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "phone", "address", "password"},
     *             @OA\Property(property="name", type="string", example="Иван Иванов"),
     *             @OA\Property(property="email", type="string", example="ivan@example.com"),
     *             @OA\Property(property="phone", type="string", example="+996555123456"),
     *             @OA\Property(property="address", type="string", example="г. Бишкек, ул. Пример, 123"),
     *             @OA\Property(property="password", type="string", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Пользователь зарегистрирован"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"phone": {"The phone number has already been taken"}, "email": {"The email has already been taken"}}
     *             )
     *         )
     *     )
     * )
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'password' => Hash::make($data['password']),
        ]);

        $user->notify(new WelcomeNotification());

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
        ], 201);
    }

    /**
     * Универсальный метод для авторизации по логину и паролю
     * В качестве логина может быть email или телефон
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Авторизация по email или телефону и паролю",
     *     description="Позволяет пользователю авторизоваться по email или телефону. Возвращает токен доступа при успешной авторизации.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login","password"},
     *             @OA\Property(property="login", type="string", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешная авторизация",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="1|fR7aG45U...nXFa")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Неверные учетные данные",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid user credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"login": {"Поле login обязательно"}, "password": {"Поле password обязательно"}}
     *             )
     *         )
     *     )
     * )
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
            'token' => $user->createToken('api_token')->plainTextToken,
        ]);
    }

    /**
     * Отправка SMS с кодом на указанный номер
     *
     * @param Request $request
     * @param SmsService $sms
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/login/phone/request-code",
     *     tags={"Auth"},
     *     summary="Запросить код по телефону",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+79998887766")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Код отправлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Code sent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Пользователь не найден"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     ),
     * )
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
     *
     * @OA\Post(
     *     path="/api/login/phone/verify-code",
     *     tags={"Auth"},
     *     summary="Подтвердить код входа",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "code"},
     *             @OA\Property(property="phone", type="string", example="+996555123456"),
     *             @OA\Property(property="code", type="string", example="1234")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Авторизация прошла успешно")
     * )
     */
    public function verifyPhoneCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $cachedCode = Cache::get("login_code:{$request->phone}");
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
