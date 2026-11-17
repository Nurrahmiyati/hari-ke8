<?php

namespace App\Http\Requests;

use App\Models\Produk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $skuUnik = Rule::unique('produk', 'sku');
        $produk = $this->route('produk');

        if ($produk instanceof Produk) {
            $skuUnik->ignore($produk);
        }

        return [
            'sku' => ['bail', 'required', 'string', 'max:50', $skuUnik],
            'nama' => ['required', 'string', 'max:255'],
            'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
            'harga' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'stok' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'integer' => ':attribute harus bilangan bulat.',
            'min' => ':attribute minimal :min.',
            'sku.max' => 'SKU maksimal :max karakter.',
            'nama.max' => 'Nama produk maksimal :max karakter.',
            'harga.max' => 'Harga maksimal :max.',
            'stok.max' => 'Stok maksimal :max.',
            'sku.unique' => 'SKU sudah digunakan produk lain.',
            'kategori_id.exists' => 'Kategori tidak ditemukan.',
        ];
    }

    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'nama' => 'Nama produk',
            'kategori_id' => 'Kategori',
            'harga' => 'Harga',
            'stok' => 'Stok',
        ];
    }
}
