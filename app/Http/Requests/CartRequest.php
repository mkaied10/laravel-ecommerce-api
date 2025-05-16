<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class CartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return  auth()->check(); 
    }

    public function rules(): array
    {
        Log::info('CartRequest all data:', $this->all());

        $rules = [
            'quantity' => ['required', 'integer', 'min:1'],
        ];

        if ($this->isMethod('post') && !$this->has('_method')) {
            $rules['product_id'] = ['required', 'integer', 'exists:products,id'];
        }

        if ($this->has('_method') || $this->isMethod('put')) {
            $rules['_method'] = ['sometimes', 'in:PUT'];
        }

        return $rules;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'quantity' => $this->input('quantity') ? (int) $this->input('quantity') : null,
            'product_id' => $this->input('product_id') ? (int) $this->input('product_id') : null,
            '_method' => $this->input('_method'),
        ]);
    }
}