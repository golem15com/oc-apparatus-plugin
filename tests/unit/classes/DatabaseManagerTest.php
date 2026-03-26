<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Golem15\Apparatus\Classes\DatabaseManager;
use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Tests for the DatabaseManager class.
 *
 * DatabaseManager is a thin wrapper extending Illuminate\Database\DatabaseManager
 * for Laravel 5.1 compatibility. A class inheritance test is sufficient here.
 */
class DatabaseManagerTest extends PluginTestCase
{
    // -------------------------------------------------------------------------
    // Inheritance
    // -------------------------------------------------------------------------

    public function testExtendsIlluminateDatabaseManager(): void
    {
        $this->assertTrue(
            is_subclass_of(
                DatabaseManager::class,
                \Illuminate\Database\DatabaseManager::class
            )
        );
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(DatabaseManager::class));
    }

    // -------------------------------------------------------------------------
    // Constructor signature — accepts Application and ConnectionFactory
    // -------------------------------------------------------------------------

    public function testConstructorAcceptsApplicationAndConnectionFactory(): void
    {
        $ref = new \ReflectionClass(DatabaseManager::class);
        $constructor = $ref->getConstructor();

        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();
        $this->assertCount(2, $params);

        $this->assertSame('app', $params[0]->getName());
        $this->assertSame('factory', $params[1]->getName());
    }
}
