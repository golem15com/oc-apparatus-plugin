<?php namespace Golem15\Apparatus\Tests\Unit\Classes;

use Golem15\Apparatus\Classes\FormComponent;
use Golem15\Apparatus\Tests\PluginTestCase;

/**
 * Tests for the FormComponent abstract class.
 *
 * FormComponent is a minimal abstract class extending Cms\Classes\ComponentBase.
 * Tests verify structure and inheritance, which is appropriate for this thin class.
 */
class FormComponentTest extends PluginTestCase
{
    // -------------------------------------------------------------------------
    // Abstract
    // -------------------------------------------------------------------------

    public function testFormComponentIsAbstract(): void
    {
        $ref = new \ReflectionClass(FormComponent::class);

        $this->assertTrue($ref->isAbstract());
    }

    // -------------------------------------------------------------------------
    // Inheritance
    // -------------------------------------------------------------------------

    public function testExtendsComponentBase(): void
    {
        $this->assertTrue(
            is_subclass_of(
                FormComponent::class,
                \Cms\Classes\ComponentBase::class
            )
        );
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(FormComponent::class));
    }

    // -------------------------------------------------------------------------
    // Instantiation — can be subclassed and instantiated
    // -------------------------------------------------------------------------

    public function testConcreteSubclassCanBeInstantiated(): void
    {
        $concrete = new class extends FormComponent {
            public function componentDetails(): array
            {
                return ['name' => 'Test', 'description' => 'Test component'];
            }
        };

        $this->assertInstanceOf(FormComponent::class, $concrete);
        $this->assertInstanceOf(\Cms\Classes\ComponentBase::class, $concrete);
    }
}
