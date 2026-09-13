<?php

namespace App\Services;

use App\Models\Token;
use Illuminate\Database\Eloquent\Collection;

class BhcReferenceService
{
    public static function normalize(?string $reference): string
    {
        $reference = strtoupper(trim(explode(';', $reference ?? '', 2)[0]));

        if (preg_match('/^(?:BHC[-\s]*)?(\d+)\/(\d{2}|\d{4})$/', $reference, $matches) === 1) {
            $year = strlen($matches[2]) === 2 ? '20'.$matches[2] : $matches[2];

            return 'BHC-'.((int) $matches[1]).'/'.$year;
        }

        return $reference;
    }

    /**
     * @return Collection<int, Token>
     */
    public static function matchingTokens(?string $bhcNumber): Collection
    {
        $reference = self::normalize($bhcNumber);

        if ($reference === '') {
            return new Collection;
        }

        return Token::query()
            ->whereNotNull('bhc_number')
            ->orderByDesc('received_on')
            ->orderByDesc('id')
            ->get(['id', 'token_number', 'bhc_number'])
            ->filter(fn (Token $token): bool => self::normalize($token->bhc_number) === $reference)
            ->values();
    }
}
