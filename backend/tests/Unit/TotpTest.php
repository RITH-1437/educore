<?php

namespace Tests\Unit;

use App\Support\Totp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * `App\Support\Totp` against the published test vectors (report 52), so the
 * in-house implementation is held to the standard authenticator apps use.
 */
class TotpTest extends TestCase
{
    /** RFC 6238 Appendix B, SHA-1, seed "12345678901234567890", 8 digits. */
    public static function rfc6238(): array
    {
        return [
            [59, '94287082'],
            [1111111109, '07081804'],
            [1111111111, '14050471'],
            [1234567890, '89005924'],
            [2000000000, '69279037'],
            [20000000000, '65353130'],
        ];
    }

    #[DataProvider('rfc6238')]
    public function test_codes_match_the_rfc_6238_vectors(int $time, string $expected): void
    {
        $secret = Totp::base32Encode('12345678901234567890');

        $this->assertSame($expected, Totp::codeForStep($secret, Totp::stepAt($time), 8));
    }

    public function test_base32_round_trips_and_matches_rfc_4648(): void
    {
        $this->assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', Totp::base32Encode('12345678901234567890'));
        $this->assertSame('MZXW6YTBOI', Totp::base32Encode('foobar'));
        $this->assertSame('foobar', Totp::base32Decode('mzxw 6ytb oi======'));

        $secret = Totp::generateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertSame(20, strlen(Totp::base32Decode($secret)));
        $this->assertNotSame($secret, Totp::generateSecret());
    }

    public function test_a_code_matches_within_one_step_of_drift_only(): void
    {
        $secret = Totp::generateSecret();
        $now = 1_760_000_000;
        $step = Totp::stepAt($now);

        $this->assertSame($step, Totp::matchingStep($secret, Totp::codeForStep($secret, $step), $now));
        $this->assertSame($step - 1, Totp::matchingStep($secret, Totp::codeForStep($secret, $step - 1), $now));
        $this->assertSame($step + 1, Totp::matchingStep($secret, ' '.substr(Totp::codeForStep($secret, $step + 1), 0, 3).' '.substr(Totp::codeForStep($secret, $step + 1), 3), $now));
        $this->assertNull(Totp::matchingStep($secret, Totp::codeForStep($secret, $step - 2), $now));
        $this->assertNull(Totp::matchingStep($secret, Totp::codeForStep($secret, $step + 2), $now));
        $this->assertNull(Totp::matchingStep($secret, 'abcdef', $now));
        $this->assertNull(Totp::matchingStep($secret, '12345', $now));
    }

    public function test_the_provisioning_uri_carries_issuer_account_and_parameters(): void
    {
        $uri = Totp::provisioningUri('JBSWY3DPEHPK3PXP', 'dara@example.edu', 'EduCore');

        $this->assertSame('otpauth://totp/EduCore:dara%40example.edu?secret=JBSWY3DPEHPK3PXP&issuer=EduCore&algorithm=SHA1&digits=6&period=30', $uri);
    }
}
