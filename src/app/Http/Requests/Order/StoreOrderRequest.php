<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // или авторизация по роли
    }

    public function rules(): array
    {
        return [
            'products' => ['required', 'array'],
            'user_id' => ['required', 'integer', 'min:1'],
            'products.*.id' => ['required', 'exists:products,id'],
            'products.*.qty' => ['required', 'integer', 'min:1'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
