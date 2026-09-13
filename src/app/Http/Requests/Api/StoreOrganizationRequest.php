<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'yandex_url' => [
                'required',
                'string',
                'max:500',
                'url',
                'regex:#^https?://yandex\.ru/maps/org/[^/]+/\d+#',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'yandex_url.required' => 'Укажите ссылку на карточку организации.',
            'yandex_url.url' => 'Это не похоже на URL.',
            'yandex_url.regex' => 'Ссылка должна вести на карточку организации в Яндекс.Картах (yandex.ru/maps/org/...).',
        ];
    }
}