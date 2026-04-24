<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Golem15\Apparatus\Classes\TranslApiController;
use Golem15\Apparatus\Tests\PluginTestCase;
use Illuminate\Http\Request;
use Mockery;
use October\Rain\Translation\Translator;

/**
 * Tests for the TranslApiController class.
 */
class TranslApiControllerTest extends PluginTestCase
{
    private Translator $translator;
    private TranslApiController $controller;

    public function setUp(): void
    {
        parent::setUp();
        $this->translator = Mockery::mock(Translator::class);
        $this->controller = new TranslApiController($this->translator);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // getTranslations — no keys supplied
    // -------------------------------------------------------------------------

    public function testReturns404WhenNoKeysProvided(): void
    {
        $request = Request::create('/transl/api', 'POST', []);

        $response = $this->controller->getTranslations($request);

        $this->assertSame(404, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('Not Found', $body['error']);
    }

    // -------------------------------------------------------------------------
    // getTranslations — with keys
    // -------------------------------------------------------------------------

    public function testReturns200WithTranslationsWhenKeysProvided(): void
    {
        $this->translator
            ->shouldReceive('get')
            ->with('plugin.namespace::lang.section.key1')
            ->andReturn('Translated Value 1');

        $this->translator
            ->shouldReceive('get')
            ->with('plugin.namespace::lang.section.key2')
            ->andReturn('Translated Value 2');

        $request = Request::create('/transl/api', 'POST', [
            'keys' => [
                'plugin.namespace::lang.section.key1',
                'plugin.namespace::lang.section.key2',
            ],
        ]);

        $response = $this->controller->getTranslations($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testReturnsCorrectTranslationMapping(): void
    {
        $this->translator
            ->shouldReceive('get')
            ->with('app::messages.hello')
            ->andReturn('Hello World');

        $this->translator
            ->shouldReceive('get')
            ->with('app::messages.goodbye')
            ->andReturn('Goodbye');

        $request = Request::create('/transl/api', 'POST', [
            'keys' => ['app::messages.hello', 'app::messages.goodbye'],
        ]);

        $response = $this->controller->getTranslations($request);
        $body = json_decode($response->getContent(), true);

        $this->assertSame('Hello World', $body['app::messages.hello']);
        $this->assertSame('Goodbye', $body['app::messages.goodbye']);
    }

    public function testCallesTranslatorGetForEachKey(): void
    {
        $keys = ['key1', 'key2', 'key3'];

        foreach ($keys as $key) {
            $this->translator
                ->shouldReceive('get')
                ->with($key)
                ->once()
                ->andReturn($key . '_translated');
        }

        $request = Request::create('/transl/api', 'POST', ['keys' => $keys]);
        $this->controller->getTranslations($request);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function testSingleKeyReturnsOnlyThatTranslation(): void
    {
        $this->translator
            ->shouldReceive('get')
            ->with('some.key')
            ->andReturn('Translated');

        $request = Request::create('/transl/api', 'POST', ['keys' => ['some.key']]);

        $response = $this->controller->getTranslations($request);
        $body = json_decode($response->getContent(), true);

        $this->assertCount(1, $body);
        $this->assertArrayHasKey('some.key', $body);
        $this->assertSame('Translated', $body['some.key']);
    }
}
