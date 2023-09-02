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
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif'];

        if (!is_string($value) && !is_a($value, 'Illuminate\Http\UploadedFile')) {
            $fail('The ' . $attribute . ' must be a string or an image.');
        }
        if (is_string($value)) {
            $image = $value;
            $base64 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $base64);
            $extension = explode('/', $mimeType)[1];

            if (!in_array($extension, $allowedExtensions)) {
                $fail('The ' . $attribute . ' must be a valid image.');
            }
        } else {
            $image = $value;
            $extension = $image->extension();
            if (!in_array($extension, $allowedExtensions)) {
                $fail('The ' . $attribute . ' must be a valid image.');
            }
        }
    }
}
