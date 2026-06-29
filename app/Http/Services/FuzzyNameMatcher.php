<?php

namespace App\Http\Services;

use Illuminate\Support\Collection;

class FuzzyNameMatcher
{
    public function tokenize(string $name): array
    {
        return array_values(
            array_filter(
                array_map('strtolower', preg_split('/\s+/', trim($name)))
            )
        );
    }

    public function tokensMatch(array $a, array $b): bool
    {
        $matchScore = 0;
        $usedB      = [];

        foreach ($a as $tokenA) {
            foreach ($b as $ib => $tokenB) {
                if (isset($usedB[$ib])) continue;

                $isMatch = false;

                if (strlen($tokenA) > 1 && strlen($tokenB) > 1) {
                    $isMatch = ($tokenA === $tokenB);
                } elseif (strlen($tokenA) === 1 && strlen($tokenB) > 1) {
                    $isMatch = ($tokenA === $tokenB[0]);
                } elseif (strlen($tokenA) > 1 && strlen($tokenB) === 1) {
                    $isMatch = ($tokenA[0] === $tokenB);
                } elseif (strlen($tokenA) === 1 && strlen($tokenB) === 1) {
                    $isMatch = ($tokenA === $tokenB);
                }

                if ($isMatch) {
                    $matchScore++;
                    $usedB[$ib] = true;
                    break;
                }
            }
        }

        return $matchScore >= 2;
    }

    public function isDuplicate(string $incomingName, string $existingName): bool
    {
        return $this->tokensMatch(
            $this->tokenize($incomingName),
            $this->tokenize($existingName)
        );
    }

    /**
     * Find all fuzzy matches from a collection of User models.
     * Collection items must have a `name` and `id` attribute.
     */
    public function findMatches(string $incomingName, Collection $candidates): Collection
    {
        $incomingTokens = $this->tokenize($incomingName);

        return $candidates->filter(
            fn($candidate) => $this->tokensMatch(
                $incomingTokens,
                $this->tokenize($candidate->name)
            )
        )->values();
    }
}
