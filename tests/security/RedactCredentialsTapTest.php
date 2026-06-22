<?php namespace Golem15\Apparatus\Tests\Security;

use Golem15\Apparatus\Tests\PluginTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Security tests for RedactCredentialsTap Monolog processor (INTG-05).
 *
 * Tests validate that the tap class scrubs sensitive keys (api_key, Bearer,
 * sk-..., x-api-key:) from log messages and context/extra arrays, while
 * preserving innocent data.
 */
#[Group('security')]
class RedactCredentialsTapTest extends PluginTestCase
{
    protected $refreshPlugins = ['Golem15.Apparatus'];

    /**
     * Helper: access protected scrubArray via anonymous subclass.
     */
    protected function scrubArray(array $data): array
    {
        $tap = new class extends \Golem15\Apparatus\Classes\Logging\RedactCredentialsTap {
            public function exposeScrubArray(array $data): array
            {
                return $this->scrubArray($data);
            }
        };

        return $tap->exposeScrubArray($data);
    }

    /**
     * Helper: access protected scrubString via anonymous subclass.
     */
    protected function scrubString(string $s): string
    {
        $tap = new class extends \Golem15\Apparatus\Classes\Logging\RedactCredentialsTap {
            public function exposeScrubString(string $s): string
            {
                return $this->scrubString($s);
            }
        };

        return $tap->exposeScrubString($s);
    }

    /**
     * INTG-05: api_key in context array is redacted; safe keys preserved.
     */
    #[Test]
    #[Group('security')]
    public function test_redact_strips_api_key_from_context_array(): void
    {
        $result = $this->scrubArray([
            'api_key' => 'sk-secret123',
            'safe'    => 'hello',
        ]);

        $this->assertSame('[REDACTED]', $result['api_key']);
        $this->assertSame('hello', $result['safe']);
    }

    /**
     * INTG-05: Bearer token in message string is redacted.
     */
    #[Test]
    #[Group('security')]
    public function test_redact_strips_bearer_from_message_string(): void
    {
        $result = $this->scrubString('Authorization: Bearer abc.def.ghi xyz');

        $this->assertSame('Authorization: Bearer [REDACTED] xyz', $result);
    }

    /**
     * INTG-05: sk-... key pattern in message string is redacted.
     */
    #[Test]
    #[Group('security')]
    public function test_redact_strips_sk_pattern_from_message(): void
    {
        $result = $this->scrubString('Used key sk-abcdef123456789012345678 for call');

        $this->assertStringContainsString('sk-[REDACTED]', $result);
        $this->assertStringNotContainsString('sk-abcdef', $result);
    }

    /**
     * INTG-05: Nested arrays are recursively scrubbed.
     */
    #[Test]
    #[Group('security')]
    public function test_redact_recurses_into_nested_array(): void
    {
        $result = $this->scrubArray([
            'headers' => [
                'authorization' => 'Bearer xyz',
            ],
        ]);

        $this->assertSame('[REDACTED]', $result['headers']['authorization']);
    }

    /**
     * INTG-05: Innocent keys are preserved unchanged.
     */
    #[Test]
    #[Group('security')]
    public function test_redact_preserves_innocent_keys(): void
    {
        $result = $this->scrubArray([
            'user_id'  => 42,
            'username' => 'alice',
        ]);

        $this->assertSame(42, $result['user_id']);
        $this->assertSame('alice', $result['username']);
    }
}
