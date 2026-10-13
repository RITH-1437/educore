<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Time-based one-time passwords (RFC 6238, HMAC-SHA1, 30-second steps), the
 * codes authenticator apps show (report 52). Secrets are RFC 4648 base32.
 *
 * Kept in-house rather than adding a package: the algorithm is a few lines
 * and is pinned by the RFC 6238 test vectors in `tests/Unit/TotpTest.php`.
 */
class Totp
{
    public const PERIOD = 30;

    public const DIGITS = 6;

    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** A new random secret: 160 bits as 32 base32 characters. */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    /** The step (counter) a Unix time falls in. */
    public static function stepAt(int $timestamp): int
    {
        return intdiv($timestamp, self::PERIOD);
    }

    /** The code for one step (RFC 4226 HOTP over the step counter). */
    public static function codeForStep(string $secret, int $step, int $digits = self::DIGITS, string $algorithm = 'sha1'): string
    {
        $hash = hash_hmac($algorithm, pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * The step a code matches, within `$window` steps either side of now (to
     * absorb clock drift), or null. Compared in constant time.
     */
    public static function matchingStep(string $secret, string $code, int $timestamp, int $window = 1): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (preg_match('/^\d{'.self::DIGITS.'}$/', $code) !== 1) {
            return null;
        }

        $now = self::stepAt($timestamp);

        for ($step = $now - $window; $step <= $now + $window; $step++) {
            if (hash_equals(self::codeForStep($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    /** The `otpauth://` URI an authenticator app reads from the QR code. */
    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer).':'.rawurlencode($account)
            .'?'.http_build_query(['secret' => $secret, 'issuer' => $issuer, 'algorithm' => 'SHA1', 'digits' => self::DIGITS, 'period' => self::PERIOD], '', '&', PHP_QUERY_RFC3986);
    }

    public static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::BASE32[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    public static function base32Decode(string $secret): string
    {
        $secret = strtoupper(rtrim(str_replace(' ', '', $secret), '='));
        $bits = '';

        foreach (str_split($secret) as $char) {
            $value = strpos(self::BASE32, $char);

            if ($value === false) {
                throw new InvalidArgumentException('The secret is not valid base32.');
            }

            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
