<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVersionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'release_note' => 'nullable|string|max:500',
            'tested' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'release_note.max' => 'Release note не может быть длиннее 500 символов',
        ];
    }
}
