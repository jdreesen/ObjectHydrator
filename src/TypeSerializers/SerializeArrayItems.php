<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\TypeSerializers;

use Attribute;
use EventSauce\ObjectHydrator\ObjectSerializer;
use EventSauce\ObjectHydrator\TypeSerializer;

use function is_array;
use function is_object;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
class SerializeArrayItems implements TypeSerializer
{
    public function serialize(mixed $value, ObjectSerializer $serializer): mixed
    {
        if ( ! is_array($value)) {
            return $value;
        }

        foreach ($value as $index => $item) {
            if (is_object($item)) {
                $value[$index] = $serializer->serializeObject($item);
            }
        }

        return $value;
    }
}
