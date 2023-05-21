<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;

class base64OrImage implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        if (!is_string($value) && !is_a($value, 'Illuminate\Http\UploadedFile')) {
            $fail('The ' . $attribute . ' must be a string or an image.');
        }
        if (is_string($value)) {
            $image = $value;
            $imageParts = explode(";base64,", $image);
            $imageTypeAux = explode("image/", $imageParts[0]);
            $imageType = $imageTypeAux[1];
            if (!in_array($imageType, ['png', 'jpg', 'jpeg', 'gif'])) {
                $fail('The ' . $attribute . ' must be a valid image.');
            }
        } else {
            $image = $value;
            $imageType = $image->extension();
            if (!in_array($imageType, ['png', 'jpg', 'jpeg', 'gif'])) {
                $fail('The ' . $attribute . ' must be a valid image.');
            }
        }
    }
}