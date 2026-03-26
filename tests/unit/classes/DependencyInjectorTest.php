<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Golem15\Apparatus\Classes\DependencyInjector;
use Golem15\Apparatus\Contracts\NeedsDependencies;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Container\Container;
use Mockery;
use October\Rain\Exception\ApplicationException;

/**
 * Tests for the DependencyInjector class.
 */
class DependencyInjectorTest extends TestCase
{
    private Container $container;
    private DependencyInjector $injector;

    public function setUp(): void
    {
        parent::setUp();
        $this->container = Mockery::mock(Container::class);
        $this->injector = new DependencyInjector($this->container);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // injectDependencies — does nothing for non-NeedsDependencies objects
    // -------------------------------------------------------------------------

    public function testDoesNothingForObjectNotImplementingNeedsDependencies(): void
    {
        $object = new \stdClass();

        // Container should NOT be called at all
        $this->container->shouldNotReceive('call');

        $this->injector->injectDependencies($object);

        // No assertion needed beyond Mockery expectations
        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // injectDependencies — calls inject* methods on NeedsDependencies objects
    // -------------------------------------------------------------------------

    public function testCallsInjectMethodsOnNeedsDependenciesObject(): void
    {
        $object = new class implements NeedsDependencies {
            public function injectFoo(\stdClass $s): void {}
            public function injectBar(\stdClass $s): void {}
            public function notAnInjectMethod(): void {}
        };

        // Only inject* methods should be called — 2 calls expected
        $this->container
            ->shouldReceive('call')
            ->with([$object, 'injectFoo'])
            ->once();

        $this->container
            ->shouldReceive('call')
            ->with([$object, 'injectBar'])
            ->once();

        $this->injector->injectDependencies($object);

        // Mockery expectations satisfy assertion count requirement
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function testIgnoresMethodsNotStartingWithInject(): void
    {
        $object = new class implements NeedsDependencies {
            public function injectService(\stdClass $s): void {}
            public function handleSomething(): void {}
            public function preInjectSetup(): void {} // starts with 'pre', not 'inject'
        };

        // Only injectService should be called
        $this->container
            ->shouldReceive('call')
            ->with([$object, 'injectService'])
            ->once();

        $this->injector->injectDependencies($object);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    // -------------------------------------------------------------------------
    // injectDependencies — wraps container exceptions in ApplicationException
    // -------------------------------------------------------------------------

    public function testWrapsContainerExceptionInApplicationExceptionWithClassName(): void
    {
        $object = new class implements NeedsDependencies {
            public function injectSomething(\stdClass $s): void {}
        };

        $this->container
            ->shouldReceive('call')
            ->with([$object, 'injectSomething'])
            ->andThrow(new \Exception('Resolution failed'));

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessageMatches('/Resolution failed/');
        $this->expectExceptionMessageMatches('/at class:/');

        $this->injector->injectDependencies($object);
    }

    public function testExceptionMessageContainsClassName(): void
    {
        $object = new NeedsDepsFixtureWithInject();

        $this->container
            ->shouldReceive('call')
            ->andThrow(new \Exception('Some error'));

        try {
            $this->injector->injectDependencies($object);
            $this->fail('Expected ApplicationException was not thrown');
        } catch (ApplicationException $e) {
            $this->assertStringContainsString(NeedsDepsFixtureWithInject::class, $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // injectDependencies — object with no inject methods
    // -------------------------------------------------------------------------

    public function testNoContainerCallsForObjectWithNoInjectMethods(): void
    {
        $object = new class implements NeedsDependencies {
            public function setup(): void {}
            public function configure(): void {}
        };

        $this->container->shouldNotReceive('call');

        $this->injector->injectDependencies($object);

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }
}

/**
 * Fixture class implementing NeedsDependencies with a named inject method.
 * Used to test that class name appears in exception message.
 */
class NeedsDepsFixtureWithInject implements NeedsDependencies
{
    public function injectSomeService(\stdClass $s): void {}
}
