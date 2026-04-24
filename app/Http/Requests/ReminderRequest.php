<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $message = trim(preg_replace('/\s+/u', ' ', (string) $this->input('message', '')));

        $this->merge([
            'message' => $message === '' ? null : $message,
        ]);
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:500', 'regex:/^[^<>]+$/'],
            'remind_at' => ['required', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Pesan pengingat wajib diisi.',
            'message.max' => 'Pesan pengingat maksimal 500 karakter.',
            'message.regex' => 'Pesan pengingat tidak boleh mengandung tag HTML atau simbol < >.',
            'remind_at.required' => 'Waktu pengingat wajib diisi.',
            'remind_at.date' => 'Format waktu pengingat tidak valid.',
            'remind_at.after' => 'Waktu pengingat harus di masa depan.',
        ];
    }
}
