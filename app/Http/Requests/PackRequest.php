<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                   => 'required|string|max:255',
            'description'            => 'nullable|string',
            'barcode'                => 'nullable|string|max:100|unique:packs,barcode,' . ($this->route('pack')?->id),
            'is_active'              => 'boolean',
            'image'                  => 'nullable|image|max:2048',

            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',

            'prices'                 => 'nullable|array',
            'prices.*.price_group_id' => 'nullable|exists:price_groups,id',
            'prices.*.buying_price'  => 'required_with:prices|numeric|min:0',
            'prices.*.selling_price' => 'required_with:prices|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Le nom du pack est obligatoire.',
            'items.required'          => 'Un pack doit contenir au moins un produit.',
            'items.min'               => 'Un pack doit contenir au moins un produit.',
            'items.*.product_id.required' => 'Sélectionnez un produit pour chaque ligne.',
            'items.*.quantity.min'    => 'La quantité doit être au moins 1.',
        ];
    }
}
