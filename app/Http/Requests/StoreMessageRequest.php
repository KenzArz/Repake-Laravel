<?php

namespace App\Http\Requests;

use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
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
            'body' => [$isOffer ? 'nullable' : 'required_without:image', 'nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'offer_amount' => [Rule::requiredIf($isOffer), 'nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required_without' => 'Tulis pesan atau lampirkan gambar.',
            'offer_amount.required' => 'Masukkan nominal tawaran.',
            'image.max' => 'Ukuran gambar maksimal 8 MB.',
        ];
    }
}
