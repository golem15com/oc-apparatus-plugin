<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Golem15\Apparatus\Classes\LaravelQueueClearServiceProvider;
use Golem15\Apparatus\Contracts\Clearer;
use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Tests for the LaravelQueueClearServiceProvider class.
 *
 * NOTE: The register() method calls $this->commands(), which registers console
 * commands with the IoC container. We test provides() (which returns []) and
 * confirm the class extends ServiceProvider and is instantiable with the
 * app() container. The full register() path would need the console kernel and
 * is better covered by an integration test.
 */
class LaravelQueueClearServiceProviderTest extends PluginTestCase
{
    // -------------------------------------------------------------------------
    // provides() returns empty array
    // -------------------------------------------------------------------------

    public function testProvidesReturnsEmptyArray(): void
    {
        $provider = new LaravelQueueClearServiceProvider(app());

        $result = $provider->provides();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // Inheritance
    // -------------------------------------------------------------------------

    public function testExtendsIlluminateServiceProvider(): void
    {
        $this->assertTrue(
            is_subclass_of(
                LaravelQueueClearServiceProvider::class,
                \Illuminate\Support\ServiceProvider::class
            )
        );
    }

    // -------------------------------------------------------------------------
    // register() — binds Clearer contract
    // -------------------------------------------------------------------------

    public function testRegisterBindsClearerContractToConcreteImplementation(): void
    {
        $provider = new LaravelQueueClearServiceProvider(app());

        // Calling register() binds Clearer contract in the app container
        // commands() may throw if artisan is not in console context; wrap it.
        try {
            $provider->register();
        } catch (\Throwable $e) {
            // commands() registration failure is expected outside CLI context
            // The binding itself is the important part
        }

        // Verify the Clearer contract is now bound
        $concrete = app()->make(Clearer::class);
        $this->assertInstanceOf(\Golem15\Apparatus\Classes\Clearer::class, $concrete);
    }
}
