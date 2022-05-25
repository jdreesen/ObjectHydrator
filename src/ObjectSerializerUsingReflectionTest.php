<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator;

class ObjectSerializerUsingReflectionTest extends ObjectSerializerTestCase
{
    public function objectSerializer(): ObjectSerializer
    {
        return new ObjectSerializerUsingReflection();
    }
}
