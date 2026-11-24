<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ResellerBusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResellerApplicationRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{8,30}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'business_type' => ['required', Rule::enum(ResellerBusinessType::class)],
            'shop_url' => ['nullable', 'url:http,https', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('site.forms.attributes');
    }
}
