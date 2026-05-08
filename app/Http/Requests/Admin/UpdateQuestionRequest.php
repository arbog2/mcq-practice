<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'stem' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
            'is_active' => ['sometimes', 'boolean'],
            'options' => ['required', 'array', 'min:2', 'max:8'],
            'options.*.content' => ['required', 'string'],
            'correct_index' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $this->validationData();
            if (isset($data['correct_index'], $data['options']) && $data['correct_index'] >= count($data['options'])) {
                $validator->errors()->add('correct_index', '正确答案索引无效。');
            }
        });
    }
}
