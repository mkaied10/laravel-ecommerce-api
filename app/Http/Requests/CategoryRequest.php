<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin; 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        log::info('CategoryRequest rules method called');
        Log::info('Request data:',$this->all());
        return [
            'name.en' => 'required|string|max:255',
            'name.ar' => 'required|string|max:255',
            'description.en' => 'nullable|string',
            'description.ar' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status.en' => 'required|in:active,not_active',
            'status.ar' => 'required|in:نشط,غير نشط',
        ];
    }

    public function messages()
    {
        return [
            'name.en.required' => __('category.name_en_required'),
            'name.ar.required' => __('category.name_ar_required'),
            'status.en.required' => __('category.status_en_required'),
            'status.ar.required' => __('category.status_ar_required'),
            'image.image' => __('category.image_invalid'),
        ];
    }
}
