<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Golem15\Apparatus\Classes\RequestSender;
use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Tests for the RequestSender class (header construction only — curl methods
 * make real HTTP calls and are not unit-tested here).
 */
class RequestSenderTest extends PluginTestCase
{
    // -------------------------------------------------------------------------
    // constructor — Content-Type header
    // -------------------------------------------------------------------------

    public function testConstructorSetsContentTypeHeader(): void
    {
        $sender = new RequestSender();

        $headers = $this->getHeaders($sender);

        $this->assertContains('Content-Type: application/json', $headers);
    }

    public function testConstructorAcceptsCustomContentType(): void
    {
        $sender = new RequestSender(null, 'application/x-www-form-urlencoded');

        $headers = $this->getHeaders($sender);

        $this->assertContains('Content-Type: application/x-www-form-urlencoded', $headers);
        $this->assertNotContains('Content-Type: application/json', $headers);
    }

    // -------------------------------------------------------------------------
    // constructor — Bearer token
    // -------------------------------------------------------------------------

    public function testConstructorWithBearerTokenAddsAuthorizationHeader(): void
    {
        $sender = new RequestSender('my-secret-token');

        $headers = $this->getHeaders($sender);

        $this->assertContains('Authorization: Bearer my-secret-token', $headers);
    }

    public function testConstructorWithoutBearerTokenDoesNotAddAuthorizationHeader(): void
    {
        $sender = new RequestSender();

        $headers = $this->getHeaders($sender);

        foreach ($headers as $header) {
            $this->assertStringNotContainsStringIgnoringCase('Authorization', $header);
        }
    }

    public function testConstructorWithNullBearerTokenDoesNotAddAuthorizationHeader(): void
    {
        $sender = new RequestSender(null);

        $headers = $this->getHeaders($sender);

        foreach ($headers as $header) {
            $this->assertStringNotContainsStringIgnoringCase('Authorization', $header);
        }
    }

    // -------------------------------------------------------------------------
    // addHeader
    // -------------------------------------------------------------------------

    public function testAddHeaderAppendsToHeadersArray(): void
    {
        $sender = new RequestSender();
        $sender->addHeader('X-Custom-Header: SomeValue');

        $headers = $this->getHeaders($sender);

        $this->assertContains('X-Custom-Header: SomeValue', $headers);
    }

    public function testAddHeaderDoesNotRemovePreviousHeaders(): void
    {
        $sender = new RequestSender('token123');
        $sender->addHeader('X-Extra: extra-value');

        $headers = $this->getHeaders($sender);

        $this->assertContains('Content-Type: application/json', $headers);
        $this->assertContains('Authorization: Bearer token123', $headers);
        $this->assertContains('X-Extra: extra-value', $headers);
    }

    public function testMultipleAddHeaderCallsAllAppend(): void
    {
        $sender = new RequestSender();
        $sender->addHeader('X-First: 1');
        $sender->addHeader('X-Second: 2');
        $sender->addHeader('X-Third: 3');

        $headers = $this->getHeaders($sender);

        $this->assertContains('X-First: 1', $headers);
        $this->assertContains('X-Second: 2', $headers);
        $this->assertContains('X-Third: 3', $headers);
    }

    // -------------------------------------------------------------------------
    // Helper: read private $headers via reflection
    // -------------------------------------------------------------------------

    private function getHeaders(RequestSender $sender): array
    {
        $ref = new \ReflectionProperty(RequestSender::class, 'headers');
        $ref->setAccessible(true);
        return $ref->getValue($sender);
    }
}
