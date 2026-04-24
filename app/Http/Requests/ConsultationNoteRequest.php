<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsultationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $body = trim(preg_replace('/\s+/u', ' ', (string) $this->input('body', '')));

        $this->merge([
            'body' => $body === '' ? null : $body,
        ]);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000', 'regex:/^[^<>]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Catatan wajib diisi.',
            'body.max' => 'Catatan maksimal 2000 karakter.',
            'body.regex' => 'Catatan tidak boleh mengandung tag HTML atau simbol < >.',
        ];
    }
}
