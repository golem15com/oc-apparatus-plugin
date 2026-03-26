<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Golem15\Apparatus\Classes\RandomAnimalGenerator;
use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Tests for the RandomAnimalGenerator class.
 */
class RandomAnimalGeneratorTest extends PluginTestCase
{
    // -------------------------------------------------------------------------
    // generate() — singular
    // -------------------------------------------------------------------------

    public function testGenerateReturnsStringInFormatAdjectiveAnimal(): void
    {
        $result = RandomAnimalGenerator::generate();

        $this->assertIsString($result);
        $this->assertStringContainsString('_', $result);
    }

    public function testGenerateReturnsDifferentValuesOnRepeatedCalls(): void
    {
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = RandomAnimalGenerator::generate();
        }

        // With 200+ adjectives and 200+ animals, the probability all 10 are identical is negligible
        $unique = array_unique($results);
        $this->assertGreaterThan(1, count($unique), 'Expected different values on repeated calls');
    }

    public function testGenerateOnlyContainsLowercaseLettersAndUnderscores(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $result = RandomAnimalGenerator::generate();
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $result, "Generated value '{$result}' contains unexpected characters");
        }
    }

    // -------------------------------------------------------------------------
    // generate(true) — plural form
    // -------------------------------------------------------------------------

    public function testGeneratePluralReturnsStringEndingWithSOrEs(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $result = RandomAnimalGenerator::generate(true);

            $this->assertIsString($result);
            $this->assertTrue(
                str_ends_with($result, 's') || str_ends_with($result, 'es'),
                "Plural form '{$result}' should end with 's' or 'es'"
            );
        }
    }

    public function testGeneratePluralContainsUnderscore(): void
    {
        $result = RandomAnimalGenerator::generate(true);

        $this->assertStringContainsString('_', $result);
    }
}
