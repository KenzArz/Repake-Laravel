<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'kondisi' => ['required', 'in:Seperti baru,Dirawat dengan baik,Bekas pakai'],
            'harga' => ['required', 'integer', 'min:0'],
            'deskripsi' => ['required', 'string', 'min:10'],
            'kota' => ['required', 'string', 'max:255'],
            'patokan_lokasi' => ['nullable', 'string', 'max:255'],
            'images' => ['required'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Wajib mengunggah minimal 1 gambar produk.',
            'category_id.required' => 'Pilih kategori produk terlebih dahulu.',
            'kondisi.required' => 'Pilih kondisi produk.',
            'kota.required' => 'Isi kota / area produk.',
        ];
    }
}
