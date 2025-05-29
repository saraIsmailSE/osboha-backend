<?php

namespace App\Http\Requests;

use App\Rules\base64OrImage;
use App\Rules\base64OrImageMaxSize;
use App\Traits\ResponseJson;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommentUpdateRequest extends FormRequest
{
    use ResponseJson;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['filled'],
            'u_comment_id' => 'required|numeric',
            'image' => [
                'nullable',
                new base64OrImage(),
                new base64OrImageMaxSize(2 * 1024 * 1024)
            ],
            'screenShots' => 'nullable|array',
            'screenShots.*' => [
                new base64OrImage(),
                new base64OrImageMaxSize(2 * 1024 * 1024)
            ],
            'start_page' => ['numeric', 'lt:end_page'],
            'end_page' => ['numeric', 'gt:start_page']
        ];
    }

    public function messages()
    {
        return [
            'body.filled' => 'النص مطلوب في حالة عدم وجود صورة',
            'u_comment_id.required' => 'معرف التعليق مطلوب',
            'u_comment_id.numeric' => 'معرف التعليق يجب أن يكون رقمًا',
            'screenShots.array' => 'لقطات الشاشة يجب أن تكون مصفوفة',
            'start_page.numeric' => 'رقم الصفحة يجب أن يكون رقمًا',
            'start_page.lt' => 'رقم الصفحة الأولى يجب أن يكون أقل من رقم الصفحة الأخيرة',
            'end_page.numeric' => 'رقم الصفحة يجب أن يكون رقمًا',
            'end_page.gt' => 'رقم الصفحة الأخيرة يجب أن يكون أكبر من رقم الصفحة الأولى',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, $this->jsonResponseWithoutMessage(
            $validator->errors()->first(),
            'data',
            500
        ));
    }
}
