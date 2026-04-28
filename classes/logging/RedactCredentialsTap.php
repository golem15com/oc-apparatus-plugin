<?php

namespace Golem15\Apparatus\Classes\Logging;

use Illuminate\Log\Logger;

/**
 * Monolog tap that scrubs credentials from log messages, contexts, and extras.
 *
 * Wire into config/logging.php channels:
 *
 *   'single' => [
 *       'driver' => 'single',
 *       'tap'    => [\Golem15\Apparatus\Classes\Logging\RedactCredentialsTap::class],
 *   ],
 *
 * Scrubs: api_key, Bearer tokens, sk-... keys, x-api-key headers, and other
 * sensitive key names (see SENSITIVE_KEYS constant).
 */
class RedactCredentialsTap
{
    /** @var string[] Keys whose values should be replaced with '[REDACTED]' */
    protected const SENSITIVE_KEYS = [
        'api_key', 'apikey', 'authorization', 'bearer',
        'password', 'secret', 'token', 'webhook_secret', 'admin_password',
        'OPENAI_API_KEY', 'ANTHROPIC_API_KEY', 'PERPLEXITY_API_KEY',
    ];

    /** @var array<string,string> Regex patterns to scrub from message strings */
    protected const PATTERNS = [
        '/Bearer\s+[A-Za-z0-9._\-+\/=]+/i' => 'Bearer [REDACTED]',
        '/sk-[A-Za-z0-9]{20,}/' => 'sk-[REDACTED]',
        '/x-api-key:\s*[^\s,]+/i' => 'x-api-key: [REDACTED]',
    ];

    /**
     * Apply the tap to each handler in the logger.
     */
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(function ($record) {
                $record['message'] = $this->scrubString((string) ($record['message'] ?? ''));
                $record['context'] = $this->scrubArray($record['context'] ?? []);
                $record['extra']   = $this->scrubArray($record['extra'] ?? []);

                return $record;
            });
        }
    }

    /**
     * Recursively scrub sensitive keys from an array.
     */
    protected function scrubArray(array $data): array
    {
        $sensitive = array_map('strtolower', static::SENSITIVE_KEYS);

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $sensitive, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->scrubArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->scrubString($value);
            }
        }

        return $data;
    }

    /**
     * Scrub credential patterns from a string.
     */
    protected function scrubString(string $s): string
    {
        foreach (static::PATTERNS as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $s);
            if ($result !== null) {
                $s = $result;
            }
        }

        return $s;
    }
}
