<?php

namespace App\Http\Requests;

use App\Models\Message;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    /**
     * Batas pengaman agar satu kiriman tidak membebani server.
     */
    public const MAX_IMAGES = 20;

    public const MAX_TOTAL_BYTES = 25 * 1024 * 1024;

    /**
     * Otorisasi peserta percakapan dilakukan di controller
     * (butuh model Conversation dari route).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $isOffer = $this->input('type') === Message::TYPE_OFFER;

        return [
            'type' => ['nullable', Rule::in([Message::TYPE_TEXT, Message::TYPE_OFFER])],
            // Kunci idempotensi dari klien: request ganda tidak membuat pesan ganda.
            'client_id' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]{8,64}$/'],
            'body' => [$isOffer ? 'nullable' : 'required_without_all:image,images', 'nullable', 'string', 'max:2000'],
            // Jalur lama (satu gambar) tetap diterima agar klien lama tidak rusak.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'images' => ['nullable', 'array', 'max:'.self::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'offer_amount' => [Rule::requiredIf($isOffer), 'nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required_without_all' => 'Tulis pesan atau lampirkan gambar.',
            'offer_amount.required' => 'Masukkan nominal tawaran.',
            'image.max' => 'Ukuran gambar maksimal 8 MB.',
            'images.max' => 'Maksimal '.self::MAX_IMAGES.' foto sekali kirim.',
            'images.*.image' => 'Semua lampiran harus berupa gambar.',
            'images.*.mimes' => 'Format foto harus JPG, PNG, WebP, atau GIF.',
            'images.*.max' => 'Ukuran tiap foto maksimal 8 MB.',
        ];
    }

    /**
     * Batasi total ukuran seluruh gambar, bukan hanya per file.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $files = array_merge(
                    $this->hasFile('image') ? [$this->file('image')] : [],
                    $this->file('images') ?? [],
                );

                $totalBytes = array_sum(array_map(
                    fn ($file) => (int) $file->getSize(),
                    $files,
                ));

                if ($totalBytes > self::MAX_TOTAL_BYTES) {
                    $validator->errors()->add(
                        'images',
                        'Total ukuran foto maksimal 25 MB. Coba pilih foto yang lebih sedikit.',
                    );
                }
            },
        ];
    }
}
