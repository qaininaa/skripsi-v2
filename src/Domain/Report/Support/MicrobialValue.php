<?php

namespace Domain\Report\Support;

/**
 * Validation + numeric coercion for microbial count values (B / F).
 *
 * Allowed inputs (stored as string):
 *   - empty / null
 *   - non-negative integer (e.g. "0", "12", "150")
 *   - "<1"   → treated as 0 colonies
 *   - "TNTC" → "Too Numerous To Count", treated as +∞ for conclusion math
 *
 * Anything else is invalid and should be rejected at the request layer
 * and visually flagged with a red border by the front-end.
 */
final class MicrobialValue
{
    /** Sentinel returned by toCount() for "TNTC" values. */
    public const TNTC = PHP_INT_MAX;

    /**
     * Whether the input is a valid microbial-count string. Empty → valid.
     */
    public static function isValid(?string $value): bool
    {
        if ($value === null) {
            return true;
        }

        $v = strtolower(trim($value));

        if ($v === '') {
            return true;
        }

        if ($v === '<1' || $v === 'tntc') {
            return true;
        }

        return ctype_digit($v);
    }

    /**
     * Convert input to a numeric count (used for alert/action evaluation).
     *
     * @return int|null  null when input is empty or invalid.
     */
    public static function toCount(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $v = strtolower(trim($value));

        return match (true) {
            $v === ''        => null,
            $v === '<1'      => 0,
            $v === 'tntc'    => self::TNTC,
            ctype_digit($v)  => (int) $v,
            default          => null,
        };
    }

    /**
     * Normalise display casing — '<1' stays as is, 'tntc' → 'TNTC',
     * digits stay as is. Empty/null preserved.
     */
    public static function normalise(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $v = trim($value);
        if ($v === '') {
            return null;
        }

        $lower = strtolower($v);
        return match (true) {
            $lower === '<1'    => '<1',
            $lower === 'tntc'  => 'TNTC',
            default            => $v,
        };
    }
}
