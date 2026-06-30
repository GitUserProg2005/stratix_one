<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'background' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],
        ];
    }
}
