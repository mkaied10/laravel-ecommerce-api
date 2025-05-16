<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
 public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin; 
    }

    public function rules(): array
{
    Log::info('ProductRequest all data:', $this->all());
    Log::info('ProductRequest files:', $this->files->all());

    return [
        'name.en' => 'required|string|max:255',
        'name.ar' => 'required|string|max:255',
        'description.en' => 'nullable|string',
        'description.ar' => 'nullable|string',
        'images' => 'nullable|array',
        'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'price' => 'required|numeric|min:0',
        'discounted_price' => 'nullable|numeric|min:0',
        'quantity' => 'required|integer|min:0',
        'status.en' => 'required|in:active,not_active',
        'status.ar' => 'required|in:نشط,غير نشط',
        'category_ids' => 'nullable|array',
        'category_ids.*' => 'exists:categories,id',
    ];
}

    public function messages()
    {
        return [
            'name.en.required' => __('product.name_en_required'),
            'name.ar.required' => __('product.name_ar_required'),
            'status.en.required' => __('product.status_en_required'),
            'status.ar.required' => __('product.status_ar_required'),
            'price.required' => __('product.price_required'),
            'quantity.required' => __('product.quantity_required'),
            'images.*.image' => __('product.image_invalid'),
        ];
    }
    
}
