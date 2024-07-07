<?php

namespace App\Traits;

trait TextTrait
{
    public function normalizeArabicText($text)
    {
        $normalizedText = str_replace('أ', 'ا', $text);

        $normalizedText = str_replace('إ', 'ا', $normalizedText);
        $normalizedText = str_replace('آ', 'ا', $normalizedText);

        return $normalizedText;
    }
}
