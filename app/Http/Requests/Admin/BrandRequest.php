<?php

namespace App\Http\Requests\Admin;

use App\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
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
        /** @var Brand|null $brand */
        $brand = $this->route('brand');

        return [
            'name' => [
                'required', 'string', 'max:255',
                // Renaming onto an existing brand would create a duplicate;
                // creating one is de-duplicated by the service instead.
                ...($brand !== null ? [Rule::unique('brands', 'name')->ignore($brand->id)] : []),
            ],
        ];
    }
}
