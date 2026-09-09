<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name'               => 'required|string|max:255',
            'variation'          => 'nullable|string|max:255',
            'description'        => 'nullable|string',
            'barcode'            => 'nullable|string|max:100|unique:products,barcode,' . $productId,
            'category_id'        => 'nullable|exists:categories,id',
            'brand_id'           => 'nullable|exists:brands,id',
            'unit_id'            => 'nullable|exists:units,id',
            'buying_price'       => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
            'tax_rate'           => 'nullable|numeric|min:0|max:100',
            'stock_quantity'     => 'nullable|integer|min:0',
            'min_stock_quantity' => 'nullable|integer|min:0',
            'expiry_date'        => 'nullable|date',
            'can_be_packed'      => 'boolean',
            'pack_quantity'      => 'nullable|integer|min:1',
            'pack_price'         => 'nullable|numeric|min:0',
            'is_active'          => 'boolean',
            'has_variations'     => 'boolean',
            'images.*'           => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Le nom du produit est obligatoire.',
            'buying_price.required'  => 'Le prix d\'achat est obligatoire.',
            'selling_price.required' => 'Le prix de vente est obligatoire.',
        ];
    }
}
