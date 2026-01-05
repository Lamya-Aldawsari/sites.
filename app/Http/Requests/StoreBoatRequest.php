<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:yacht,sailboat,speedboat,fishing_boat,catamaran,houseboat,other',
            'capacity' => 'required|integer|min:1',
            'length' => 'nullable|integer|min:1',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'make' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'hourly_rate' => 'required|numeric|min:0',
            'daily_rate' => 'required|numeric|min:0',
            'weekly_rate' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'amenities' => 'nullable|array',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ];
    }
}

