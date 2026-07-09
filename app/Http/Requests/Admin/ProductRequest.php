<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'brand' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:4096'],
            'removed_image_ids' => ['nullable', 'array'],
            'removed_image_ids.*' => ['integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
