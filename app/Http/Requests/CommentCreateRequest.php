<?php

namespace App\Http\Requests;

use App\Rules\base64OrImage;
use App\Rules\base64OrImageMaxSize;
use App\Traits\ResponseJson;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommentCreateRequest extends FormRequest
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
            'body' => [
                'filled',
                Rule::requiredIf(function () {
                    return $this->input('type') != "thesis"
                        && $this->input('type') != 'screenshot'
                        && (!$this->has('image')); //use has not hasFile since the image could be base64 string
                }),

                // function ($attribute, $value, $fail) {
                //     if ($this->input('type') != "thesis" && !$this->hasFile('image') && !$this->filled('body')) {
                //         $fail('النص مطلوب في حالة عدم وجود صورة');
                //     }
                // },
            ],
            'book_id' => 'required_without:post_id|numeric',
            'post_id' => 'required_without:book_id|numeric',
            'comment_id' => 'nullable|numeric',
            'type' => ['required', Rule::in(['thesis', 'comment'])],
            'image' => [
                new base64OrImage(),
                new base64OrImageMaxSize(2 * 1024 * 1024),
                Rule::requiredIf(function () {
                    return $this->input('type') != "thesis"
                        && $this->input('type') != "screenshot"
                        && !$this->filled('body');
                }),
            ],
            'screenShots' => 'nullable|array',
            'screenShots.*' => [new base64OrImage(), new base64OrImageMaxSize(2 * 1024 * 1024)],
            'start_page' => 'required_if:type,thesis|numeric',
            'end_page' => 'required_if:type,thesis|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'النص مطلوب في حالة عدم وجود صورة',
            'body.filled' => 'النص مطلوب',
            'book_id.required_without' => 'معرف الكتاب مطلوب',
            'post_id.required_without' => 'معرف المنشور مطلوب',
            'type.required' => 'النوع مطلوب',
            'type.in' => 'النوع غير صحيح',
            'image.required' => 'الصورة مطلوبة في حالة عدم وجود نص',
            'image.filled' => 'الصورة مطلوبة',
            'screenShots.array' => 'السكرينات مطلوبة',
            'start_page.required_if' => 'الصفحة الأولى مطلوبة',
            'end_page.required_if' => 'الصفحة الأخيرة مطلوبة',
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
