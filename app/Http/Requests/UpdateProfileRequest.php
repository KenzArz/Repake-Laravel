<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Siapkan data sebelum validasi (username tanpa awalan @).
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('username')) {
            $this->merge([
                'username' => ltrim(trim((string) $this->input('username')), '@'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($this->user()?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'kota' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_avatar' => ['nullable'],
        ];
    }

    /**
     * Pesan kustom untuk error validasi.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap / username wajib diisi.',
            'name.max' => 'Nama tidak boleh melebihi 100 karakter.',
            'username.max' => 'Username tidak boleh melebihi 50 karakter.',
            'username.unique' => 'Username sudah dipakai pengguna lain.',
            'phone.max' => 'Nomor telepon tidak boleh melebihi 30 karakter.',
            'kota.max' => 'Lokasi tidak boleh melebihi 100 karakter.',
            'bio.max' => 'Bio tidak boleh melebihi 500 karakter.',
            'avatar.image' => 'Berkas avatar harus berupa gambar.',
            'avatar.mimes' => 'Format gambar avatar yang didukung: JPEG, PNG, JPG, WEBP.',
            'avatar.max' => 'Ukuran foto profil maksimal adalah 2 MB.',
        ];
    }
}
