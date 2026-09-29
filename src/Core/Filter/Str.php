<?php

namespace Skeletor\Core\Filter;

/**
 * Small string filters that used to come from laminas-filter / laminas-i18n.
 *
 * Those two packages were pulled in for exactly two things: an int cast and an alphanumeric
 * strip. The int cast is now inline at the call sites; the strip lives here so the character
 * class is defined once rather than copied into every Filter class.
 */
final class Str
{
    /**
     * Strip everything that is not a letter or a digit.
     *
     * Unicode-aware on purpose. laminas-i18n's Alnum used the intl extension when it was
     * available, which meant names like "Đorđe" or "Šćepan" survived the filter; an ASCII-only
     * [^a-zA-Z0-9] would silently mangle them, and this framework runs Serbian-language apps.
     *
     * @param bool $allowWhiteSpace keep spaces, tabs and newlines (Alnum's constructor flag)
     */
    public static function alnum(?string $value, bool $allowWhiteSpace = false): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $pattern = $allowWhiteSpace ? '/[^\p{L}\p{N}\s]/u' : '/[^\p{L}\p{N}]/u';

        return (string) preg_replace($pattern, '', $value);
    }
}
