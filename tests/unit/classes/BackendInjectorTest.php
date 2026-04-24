<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Golem15\Apparatus\Classes\BackendInjector;
use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Tests for the BackendInjector class.
 *
 * NOTE: BackendInjector::__construct() calls Backend\Classes\Controller::extend(),
 * which registers a closure on the backend controller. This is safe in the test
 * environment because WinterCMS is booted — the extend() call simply queues the
 * closure and does not instantiate a controller.
 *
 * We test asset and handler registration by reading protected properties via
 * reflection after calling the add* methods.
 */
class BackendInjectorTest extends PluginTestCase
{
    private BackendInjector $injector;

    public function setUp(): void
    {
        parent::setUp();
        $this->injector = new BackendInjector();
    }

    // -------------------------------------------------------------------------
    // addJs
    // -------------------------------------------------------------------------

    public function testAddJsStoresAssetInJsAssetsArray(): void
    {
        $this->injector->addJs('/path/to/file.js');

        $assets = $this->readProtected($this->injector, 'jsAssets');

        $this->assertCount(1, $assets);
        $this->assertSame('/path/to/file.js', $assets[0]['path']);
    }

    public function testAddJsStoresAttributesAlongWithPath(): void
    {
        $this->injector->addJs('/some/script.js', ['build' => 'v1', 'defer' => true]);

        $assets = $this->readProtected($this->injector, 'jsAssets');

        $this->assertSame(['build' => 'v1', 'defer' => true], $assets[0]['attributes']);
    }

    public function testAddJsWithNoAttributesDefaultsToEmptyArray(): void
    {
        $this->injector->addJs('/script.js');

        $assets = $this->readProtected($this->injector, 'jsAssets');

        $this->assertSame([], $assets[0]['attributes']);
    }

    public function testAddJsMultipleCallsAllAppend(): void
    {
        $this->injector->addJs('/a.js');
        $this->injector->addJs('/b.js');

        $assets = $this->readProtected($this->injector, 'jsAssets');

        $this->assertCount(2, $assets);
        $this->assertSame('/a.js', $assets[0]['path']);
        $this->assertSame('/b.js', $assets[1]['path']);
    }

    // -------------------------------------------------------------------------
    // addCss
    // -------------------------------------------------------------------------

    public function testAddCssStoresAssetInCssAssetsArray(): void
    {
        $this->injector->addCss('/path/to/style.css');

        $assets = $this->readProtected($this->injector, 'cssAssets');

        $this->assertCount(1, $assets);
        $this->assertSame('/path/to/style.css', $assets[0]['path']);
    }

    public function testAddCssStoresAttributes(): void
    {
        $this->injector->addCss('/style.css', ['media' => 'print']);

        $assets = $this->readProtected($this->injector, 'cssAssets');

        $this->assertSame(['media' => 'print'], $assets[0]['attributes']);
    }

    // -------------------------------------------------------------------------
    // addAjaxHandler
    // -------------------------------------------------------------------------

    public function testAddAjaxHandlerStoresHandlerInAjaxHandlersArray(): void
    {
        $fn = function () { return 'result'; };

        $this->injector->addAjaxHandler('onMyAction', $fn, 'SomeExtension');

        $handlers = $this->readProtected($this->injector, 'ajaxHandlers');

        $this->assertCount(1, $handlers);
        $this->assertSame('onMyAction', $handlers[0]['name']);
        $this->assertSame($fn, $handlers[0]['function']);
        $this->assertSame('SomeExtension', $handlers[0]['extension']);
    }

    public function testAddAjaxHandlerWithNullExtension(): void
    {
        $fn = function () {};
        $this->injector->addAjaxHandler('onAction', $fn);

        $handlers = $this->readProtected($this->injector, 'ajaxHandlers');

        $this->assertNull($handlers[0]['extension']);
    }

    // -------------------------------------------------------------------------
    // Helper: read protected/private property via reflection
    // -------------------------------------------------------------------------

    private function readProtected(object $object, string $property): mixed
    {
        $ref = new \ReflectionProperty($object::class, $property);
        $ref->setAccessible(true);
        return $ref->getValue($object);
    }
}
