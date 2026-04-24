<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Closure;
use Golem15\Apparatus\Classes\Pipeline;
use Golem15\Apparatus\Contracts\Pipe;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Pipeline class.
 */
class PipelineTest extends TestCase
{
    // -------------------------------------------------------------------------
    // thenReturn — no pipes
    // -------------------------------------------------------------------------

    public function testPassableIsReturnedUnchangedWithNoPipes(): void
    {
        $result = Pipeline::send('hello')->through([])->thenReturn();

        $this->assertSame('hello', $result);
    }

    public function testPassableArrayIsReturnedUnchangedWithNoPipes(): void
    {
        $input = ['a' => 1, 'b' => 2];
        $result = Pipeline::send($input)->through([])->thenReturn();

        $this->assertSame($input, $result);
    }

    // -------------------------------------------------------------------------
    // callable pipes
    // -------------------------------------------------------------------------

    public function testSingleCallablePipeModifiesPassable(): void
    {
        $pipe = function ($passable, $next) {
            return $next($passable . '_modified');
        };

        $result = Pipeline::send('hello')->through([$pipe])->thenReturn();

        $this->assertSame('hello_modified', $result);
    }

    public function testMultipleCallablePipesExecuteInOrder(): void
    {
        $pipe1 = function ($passable, $next) {
            return $next($passable . '_first');
        };
        $pipe2 = function ($passable, $next) {
            return $next($passable . '_second');
        };

        $result = Pipeline::send('start')->through([$pipe1, $pipe2])->thenReturn();

        $this->assertSame('start_first_second', $result);
    }

    // -------------------------------------------------------------------------
    // object pipes (Pipe contract)
    // -------------------------------------------------------------------------

    public function testObjectPipeUsingHandleMethod(): void
    {
        $pipe = new class implements Pipe {
            public function handle(mixed $passable, Closure $next)
            {
                return $next($passable . '_handled');
            }
        };

        $result = Pipeline::send('value')->through([$pipe])->thenReturn();

        $this->assertSame('value_handled', $result);
    }

    // -------------------------------------------------------------------------
    // string class name pipes
    // -------------------------------------------------------------------------

    public function testStringClassNamePipeIsInstantiatedAndHandled(): void
    {
        $result = Pipeline::send('test')->through([StringPipeFixture::class])->thenReturn();

        $this->assertSame('test_from_class', $result);
    }

    // -------------------------------------------------------------------------
    // then() with custom destination
    // -------------------------------------------------------------------------

    public function testThenWithCustomDestinationClosure(): void
    {
        $result = Pipeline::send('data')
            ->through([])
            ->then(function ($passable) {
                return strtoupper($passable);
            });

        $this->assertSame('DATA', $result);
    }

    public function testThenWithPipesAndCustomDestination(): void
    {
        $pipe = function ($passable, $next) {
            return $next($passable . '_piped');
        };

        $result = Pipeline::send('value')
            ->through([$pipe])
            ->then(function ($passable) {
                return '[' . $passable . ']';
            });

        $this->assertSame('[value_piped]', $result);
    }

    // -------------------------------------------------------------------------
    // invalid pipe type
    // -------------------------------------------------------------------------

    public function testInvalidPipeTypeThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Pass an integer as a pipe — not callable, not object, not string class
        Pipeline::send('test')->through([42])->thenReturn();
    }
}

/**
 * Test fixture class implementing the Pipe contract.
 */
class StringPipeFixture implements Pipe
{
    public function handle(mixed $passable, Closure $next)
    {
        return $next($passable . '_from_class');
    }
}
