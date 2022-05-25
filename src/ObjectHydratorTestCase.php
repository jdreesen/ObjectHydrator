<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

use EventSauce\ObjectHydrator\Fixtures\ClassThatContainsAnotherClass;
use EventSauce\ObjectHydrator\Fixtures\ClassThatHasMultipleCastersOnSingleProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassThatRenamesInputForClassWithMultipleProperties;
use EventSauce\ObjectHydrator\Fixtures\ClassThatUsesClassWithMultipleProperties;
use EventSauce\ObjectHydrator\Fixtures\ClassWithCamelCaseProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithComplexTypeThatIsMapped;
use EventSauce\ObjectHydrator\Fixtures\ClassWithFormattedDateTimeInput;
use EventSauce\ObjectHydrator\Fixtures\ClassWithMappedStringProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithNotCastedDateTimeInput;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyCasting;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyMappedFromNestedKey;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyThatUsesListCasting;
use EventSauce\ObjectHydrator\Fixtures\ClassWithPropertyThatUsesListCastingToClasses;
use EventSauce\ObjectHydrator\Fixtures\ClassWithStaticConstructor;
use EventSauce\ObjectHydrator\Fixtures\ClassWithUnmappedStringProperty;
use EventSauce\ObjectHydrator\Fixtures\ClassWithUuidProperty;
use EventSauce\ObjectHydrator\FixturesFor81\ClassWithEnumProperty;
use EventSauce\ObjectHydrator\FixturesFor81\CustomEnum;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class ObjectHydratorTestCase extends TestCase
{
    /**
     * @test
     */
    public function properties_can_be_mapped_from_a_specific_key(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithMappedStringProperty::class, ['my_name' => 'Frank']);

        self::assertInstanceOf(ClassWithMappedStringProperty::class, $object);
        self::assertEquals('Frank', $object->name);
    }

    /**
     * @test
     */
    public function mapping_a_nested_key(): void
    {
        $hydrator = $this->createObjectHydrator();

        /** @var ClassWithPropertyMappedFromNestedKey $object */
        $object = $hydrator->hydrateObject(
            ClassWithPropertyMappedFromNestedKey::class,
            ['nested' => ['name' => 'Frank']]
        );

        self::assertInstanceOf(ClassWithPropertyMappedFromNestedKey::class, $object);
        self::assertEquals('Frank', $object->name);
    }

    /**
     * @test
     */
    public function trying_to_map_a_nested_key_from_shallow_input(): void
    {
        $hydrator = $this->createObjectHydrator();

        $this->expectExceptionObject(UnableToHydrateObject::dueToError(ClassWithPropertyMappedFromNestedKey::class));

        $hydrator->hydrateObject(ClassWithPropertyMappedFromNestedKey::class, ['nested' => 'Frank']);
    }

    /**
     * @test
     */
    public function mapping_to_a_list_of_objects(): void
    {
        $hydrator = $this->createObjectHydrator();
        $input = [['my_name' => 'Frank'], ['my_name' => 'Renske']];

        $objects = $hydrator->hydrateObjects(ClassWithMappedStringProperty::class, $input);

        self::assertContainsOnlyInstancesOf(ClassWithMappedStringProperty::class, $objects);
    }

    /**
     * @test
     */
    public function mapping_to_an_array_of_objects(): void
    {
        $hydrator = $this->createObjectHydrator();
        $input = [['my_name' => 'Frank'], ['my_name' => 'Renske']];

        $objects = $hydrator->hydrateObjects(ClassWithMappedStringProperty::class, $input)->toArray();

        self::assertIsArray($objects);
        self::assertCount(2, $objects);
        self::assertContainsOnlyInstancesOf(ClassWithMappedStringProperty::class, $objects);
    }

    /**
     * @test
     */
    public function properties_are_mapped_by_name_by_default(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithUnmappedStringProperty::class, ['name' => 'Frank']);

        self::assertInstanceOf(ClassWithUnmappedStringProperty::class, $object);
        self::assertEquals('Frank', $object->name);
    }

    /**
     * @test
     */
    public function properties_can_be_cast_to_a_different_type(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithPropertyCasting::class, ['age' => '1234']);

        self::assertInstanceOf(ClassWithPropertyCasting::class, $object);
        self::assertEquals(1234, $object->age);
    }

    /**
     * @test
     */
    public function list_type_properties_can_be_cast_to_a_different_type(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithPropertyThatUsesListCasting::class, ['ages' => ['1234', '2345']]);

        self::assertInstanceOf(ClassWithPropertyThatUsesListCasting::class, $object);
        self::assertEquals([1234, 2345], $object->ages);
    }

    /**
     * @test
     */
    public function list_values_can_be_cast_to_objects(): void
    {
        $expectedChildren = [
            new ClassWithUnmappedStringProperty('Frank'),
            new ClassWithUnmappedStringProperty('Renske'),
        ];
        $hydrator = $this->createObjectHydrator();

        $payload = [
            'children' => [
                ['name' => 'Frank'],
                ['name' => 'Renske'],
            ],
        ];

        $object = $hydrator->hydrateObject(ClassWithPropertyThatUsesListCastingToClasses::class, $payload);

        self::assertInstanceOf(ClassWithPropertyThatUsesListCastingToClasses::class, $object);
        self::assertEquals($expectedChildren, $object->children);
    }

    /**
     * @test
     */
    public function using_default_key_conversion_from_snake_case(): void
    {
        $hydrator = $this->createObjectHydrator(
            new HydrationDefinitionProviderUsingReflection(null, new KeyFormatterForSnakeCasing())
        );

        $object = $hydrator->hydrateObject(ClassWithCamelCaseProperty::class, ['snake_case' => 'camelCase']);

        self::assertInstanceOf(ClassWithCamelCaseProperty::class, $object);
        self::assertEquals('camelCase', $object->snakeCase);
    }

    /**
     * @test
     */
    public function objects_can_have_static_constructors(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithStaticConstructor::class, ['name' => 'Renske']);

        self::assertInstanceOf(ClassWithStaticConstructor::class, $object);
        self::assertEquals('Renske', $object->name);
    }

    /**
     * @test
     */
    public function properties_are_mapped_automatically(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassThatContainsAnotherClass::class, ['child' => ['name' => 'Frank']]);

        self::assertInstanceOf(ClassThatContainsAnotherClass::class, $object);
        self::assertEquals('Frank', $object->child->name);
    }

    /**
     * @test
     */
    public function hydrating_a_complex_object_that_uses_property_casting(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithComplexTypeThatIsMapped::class, ['child' => 'de Jonge']);

        self::assertInstanceOf(ClassWithComplexTypeThatIsMapped::class, $object);
        self::assertEquals('de Jonge', $object->child->name);
    }

    /**
     * @test
     */
    public function hydrating_a_class_with_a_formatted_date(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithFormattedDateTimeInput::class, ['date' => '24-11-1987']);

        self::assertInstanceOf(ClassWithFormattedDateTimeInput::class, $object);
        self::assertEquals('1987-11-24 00:00:00', $object->date->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function hydrating_a_class_with_a_not_casted_date_input(): void
    {
        $hydrator = $this->createObjectHydrator();

        $object = $hydrator->hydrateObject(ClassWithNotCastedDateTimeInput::class, ['date' => '2022-01-01 12:00:00']);

        self::assertInstanceOf(ClassWithNotCastedDateTimeInput::class, $object);
        self::assertEquals('2022-01-01 12:00:00', $object->date->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function missing_properties_result_in_an_exception(): void
    {
        $hydrator = $this->createObjectHydrator();

        $this->expectExceptionObject(UnableToHydrateObject::dueToError(ClassWithUnmappedStringProperty::class));

        $hydrator->hydrateObject(ClassWithUnmappedStringProperty::class, []);
    }

    /**
     * @test
     * @requires PHP >= 8.1
     */
    public function hydrating_an_object_with_an_enum(): void
    {
        $hydrator = $this->createObjectHydratorFor81();

        $object = $hydrator->hydrateObject(ClassWithEnumProperty::class, ['enum' => 'one']);

        self::assertEquals(CustomEnum::VALUE_ONE, $object->enum);
    }

    /**
     * @test
     */
    public function hydrating_classes_that_do_not_exist_cause_an_exception(): void
    {
        $hydrator = $this->createObjectHydrator();

        $this->expectExceptionObject(UnableToHydrateObject::dueToError('ThisClass\\DoesNotExist'));

        $hydrator->hydrateObject('ThisClass\\DoesNotExist', []);
    }

    /**
     * @test
     */
    public function constructing_a_property_with_multiple_casters(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['child' => 12345];
        $object = $hydrator->hydrateObject(ClassThatHasMultipleCastersOnSingleProperty::class, $payload);

        self::assertInstanceOf(ClassThatHasMultipleCastersOnSingleProperty::class, $object);
        self::assertEquals('12345', $object->child->name);
    }

    /**
     * @test
     */
    public function mapping_multiple_keys_to_one_object(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['value' => 'dog', 'name' => 'Rover', 'age' => 2];
        $object = $hydrator->hydrateObject(ClassThatUsesClassWithMultipleProperties::class, $payload);

        self::assertInstanceOf(ClassThatUsesClassWithMultipleProperties::class, $object);
        self::assertEquals('dog', $object->value);
        self::assertEquals('Rover', $object->child->name);
        self::assertEquals(2, $object->child->age);
    }

    /**
     * @test
     */
    public function casting_a_property_to_a_uuid(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['id' => '9f960d77-7c9b-4bfd-9fc4-62d141efc7e5'];
        $object = $hydrator->hydrateObject(ClassWithUuidProperty::class, $payload);

        self::assertInstanceOf(ClassWithUuidProperty::class, $object);
        self::assertInstanceOf(UuidInterface::class, $object->id);
        self::assertTrue($object->id->equals(Uuid::fromString('9f960d77-7c9b-4bfd-9fc4-62d141efc7e5')));
    }

    /**
     * @test
     */
    public function mapping_multiple_keys_to_one_object_with_renames(): void
    {
        $hydrator = $this->createObjectHydrator();

        $payload = ['name' => 'Rover', 'mapped_age' => 2];
        $object = $hydrator->hydrateObject(ClassThatRenamesInputForClassWithMultipleProperties::class, $payload);

        self::assertInstanceOf(ClassThatRenamesInputForClassWithMultipleProperties::class, $object);
        self::assertEquals('Rover', $object->child->name);
        self::assertEquals(2, $object->child->age);
    }

    protected function createObjectHydratorFor81(): ObjectHydrator
    {
        return $this->createObjectHydrator();
    }

    abstract protected function createObjectHydrator(HydrationDefinitionProvider $definitionProvider = null): ObjectHydrator;
}
