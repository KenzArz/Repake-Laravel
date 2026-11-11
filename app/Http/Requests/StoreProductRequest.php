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
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'condition' => ['required', 'in:baru,bekas_baik,bekas_layak'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'min:10'],
            'cod_location' => ['nullable', 'string', 'max:255'],
            'location_notes' => ['nullable', 'string', 'max:255'],
            'images' => ['required', 'array', 'min:1', 'max:3'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Wajib mengunggah minimal 1 gambar produk.',
            'images.max' => 'Maksimal gambar yang dapat diunggah adalah 3 gambar.',
            'category_id.required' => 'Pilih kategori produk terlebih dahulu.',
            'condition.required' => 'Pilih kondisi produk.',
        ];
    }
}
