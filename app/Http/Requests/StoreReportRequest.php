<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'in:penipuan,barang_palsu,konten_tidak_pantas,harga_tidak_wajar,lainnya'],
            'keterangan' => ['required', 'string', 'min:5', 'max:1000'],
            'bukti' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
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
            'alasan.required' => 'Silakan pilih alasan pelaporan barang.',
            'alasan.in' => 'Alasan pelaporan yang dipilih tidak valid.',
            'keterangan.required' => 'Penjelasan atau keterangan laporan wajib diisi.',
            'keterangan.min' => 'Keterangan laporan minimal berisi 5 karakter.',
            'keterangan.max' => 'Keterangan laporan maksimal 1000 karakter.',
            'bukti.image' => 'Berkas bukti harus berupa gambar.',
            'bukti.mimes' => 'Format gambar bukti yang didukung: JPEG, PNG, JPG, WEBP.',
            'bukti.max' => 'Ukuran berkas bukti maksimal adalah 2 MB.',
        ];
    }
}
