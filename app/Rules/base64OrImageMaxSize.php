<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class base64OrImageMaxSize implements ValidationRule
{
    protected $maxSize;

    /**
     * Create a new rule instance.
     * 
     * @param  int  $maxSize The maximum size of the image in bytes.
     */
    public function __construct($maxSize)
    {
        $this->maxSize = $maxSize;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            $image = $value;
            $imageBase64 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
            $size = strlen($imageBase64);
            $bytes = ($size / 4) * 3;

            if ($bytes > $this->maxSize) {
                $fail('The ' . $attribute . ' must be less than ' . $this->maxSize . ' bytes.');
            }
        } else {
            $image = $value;

            if ($image->getSize() > $this->maxSize) {
                $fail('The ' . $attribute . ' must be less than ' . $this->maxSize . ' bytes.');
            }
        }
    }
}
