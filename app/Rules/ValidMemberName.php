<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidMemberName implements Rule
{
    public function passes($attribute, $value)
    {
        $name = trim((string) $value);
        if ($name === '') {
            return false;
        }

        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) < 2) {
            return false;
        }

        $fullWords = array_filter($words, fn($word) => $this->isFullWord($word));

        if (count($words) === 2) {
            return count($fullWords) === 2;
        }

        return count($fullWords) >= 2;
    }

    public function message()
    {
        return 'The :attribute must contain at least two names.';
        // return 'The :attribute must contain at least two names. For exactly two words, both must be full names. If more than two words are provided, at least two of them must be full names.';
    }

    protected function isFullWord(string $word): bool
    {
        if ($this->isInitialWord($word)) {
            return false;
        }

        $normalized = trim($word, '.');
        $lettersOnly = preg_replace('/[^\p{L}]/u', '', $normalized);

        return mb_strlen($lettersOnly) >= 2 && preg_match('/^[\p{L}\'"’\-]+$/u', $normalized) === 1;
    }

    protected function isInitialWord(string $word): bool
    {
        return preg_match('/^[A-Za-z](?:\.)?$/', $word) === 1
            || preg_match('/^(?:[A-Za-z]\.)+$/', $word) === 1;
    }
}
