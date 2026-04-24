<?php namespace Golem15\Apparatus\Tests\Unit\Models;

use Carbon\Carbon;
use Golem15\Apparatus\Models\PersonalApiToken;
use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Tests for the PersonalApiToken model.
 */
class PersonalApiTokenModelTest extends PluginTestCase
{
    // -------------------------------------------------------------------------
    // generateToken
    // -------------------------------------------------------------------------

    public function testGenerateTokenReturnsStringStartingWithG15Prefix(): void
    {
        $token = PersonalApiToken::generateToken();

        $this->assertStringStartsWith('g15_', $token);
    }

    public function testGenerateTokenIs44CharactersTotal(): void
    {
        $token = PersonalApiToken::generateToken();

        // 'g15_' (4 chars) + bin2hex(random_bytes(20)) = 40 hex chars = 44 total
        $this->assertSame(44, strlen($token));
    }

    public function testGenerateTokenReturnsUniqueValuesOnMultipleCalls(): void
    {
        $token1 = PersonalApiToken::generateToken();
        $token2 = PersonalApiToken::generateToken();

        $this->assertNotSame($token1, $token2);
    }

    // -------------------------------------------------------------------------
    // hashToken
    // -------------------------------------------------------------------------

    public function testHashTokenReturnsSha256Hash(): void
    {
        $hash = PersonalApiToken::hashToken('some_plain_token');

        // SHA-256 produces 64 hex characters
        $this->assertSame(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    public function testHashTokenSameInputAlwaysProducesSameHash(): void
    {
        $plainToken = 'g15_test_token_value_12345';

        $hash1 = PersonalApiToken::hashToken($plainToken);
        $hash2 = PersonalApiToken::hashToken($plainToken);

        $this->assertSame($hash1, $hash2);
    }

    // -------------------------------------------------------------------------
    // isExpired
    // -------------------------------------------------------------------------

    public function testIsExpiredReturnsFalseWhenExpiresAtIsNull(): void
    {
        $token = new PersonalApiToken();
        $token->expires_at = null;

        $this->assertFalse($token->isExpired());
    }

    public function testIsExpiredReturnsTrueWhenExpiresAtIsInThePast(): void
    {
        $token = new PersonalApiToken();
        $token->expires_at = Carbon::now()->subHour();

        $this->assertTrue($token->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenExpiresAtIsInTheFuture(): void
    {
        $token = new PersonalApiToken();
        $token->expires_at = Carbon::now()->addHour();

        $this->assertFalse($token->isExpired());
    }
}
