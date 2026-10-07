<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Untuk update: image tidak wajib
        // Untuk create: image wajib
        $imageRule = ($this->isMethod('PUT') || $this->isMethod('PATCH'))
            ? 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            : 'required|image|mimes:jpeg,png,jpg,gif|max:2048';

        return [
            'name'                  => 'required|string|max:255',
            'description'           => 'required|string|min:10',
            'category'              => 'required|string|max:255',
            'condition'             => 'required|in:excellent,good,fair,poor',
            'price'                 => 'required|numeric|min:1000',

            // Conditional image rule
            'image'                 => $imageRule,

            // Barter
            'allow_barter'          => 'boolean',
            'barter_preferences'    => 'nullable|string|max:500',

            // Trade-in
            'allow_trade_in'        => 'boolean',
            'trade_in_value'        => 'nullable|numeric|min:0',
            'trade_in_preferences'  => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                 => 'Nama produk wajib diisi',
            'description.required'          => 'Deskripsi produk wajib diisi',
            'description.min'               => 'Deskripsi minimal 10 karakter',
            'category.required'             => 'Kategori produk wajib diisi',
            'condition.required'            => 'Kondisi produk wajib dipilih',
            'price.required'                => 'Harga produk wajib diisi',
            'price.min'                     => 'Harga minimal Rp 1.000',

            // Image messages
            'image.required'                => 'Gambar produk wajib diupload',
            'image.image'                   => 'File harus berupa gambar',
            'image.mimes'                   => 'Format gambar harus jpeg, png, jpg, atau gif',
            'image.max'                     => 'Ukuran gambar maksimal 2MB',
        ];
    }
}
