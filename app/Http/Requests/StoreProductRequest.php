<?php

namespace App\Http\Requests;

use App\Services\ProductImageStorage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
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
            'cod_place_name' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'cod_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'cod_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'patokan_lokasi' => ['nullable', 'string', 'max:255'],
            'images' => ['required', 'array', 'max:'.ProductImageStorage::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Wajib mengunggah minimal 1 gambar produk.',
            'images.max' => 'Maksimal '.ProductImageStorage::MAX_IMAGES.' gambar produk.',
            'images.*.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'images.*.max' => 'Ukuran tiap gambar maksimal 5MB.',
            'category_id.required' => 'Pilih kategori produk terlebih dahulu.',
            'kondisi.required' => 'Pilih kondisi produk.',
            'kota.required' => 'Isi kota / area produk.',
        ];
    }
}
