<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $locales = ['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es'];

        $rules = [
            'alias' => 'required|unique:contents|max:255|alpha_dash',
            'default_name' => 'required|max:255',
            'subsection_id' => 'required|exists:subsections,id',
            'access_type' => 'required|integer|between:0,255',
        ];

        // Добавляем правила для каждого языка
        foreach ($locales as $locale) {
            $rules["names.{$locale}"] = 'required|string|max:500';
            $rules["descriptions.{$locale}"] = 'nullable|string|max:1000';
        }

        $rules['image_links'] = 'nullable|array';
        $rules['image_links.*'] = 'url|max:500';
        $rules['video_links'] = 'nullable|array';
        $rules['video_links.*'] = 'url|max:500';
        $rules['available_locales'] = 'required|array';
        $rules['available_locales.*'] = 'in:' . implode(',', $locales);
        $rules['modules'] = 'nullable|array';
        $rules['modules.*'] = 'exists:modules,id';

        return $rules;
    }

    public function messages()
    {
        return [
            'names.*.required' => 'Название обязательно для всех языков',
            'alias.alpha_dash' => 'Alias может содержать только буквы, цифры, дефисы и подчеркивания',
        ];
    }
}
