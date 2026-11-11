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
            'body' => [$isOffer ? 'nullable' : 'required_without:image', 'nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'offer_amount' => [Rule::requiredIf($isOffer), 'nullable', 'integer', 'min:1'],
        ];
    }
}
