<?php

declare(strict_types=1);

namespace EventSauce\ObjectHydrator\Fixtures;

class ClassWithListOfObjects
{
    public function __construct(public array $children)
    {
    }
}
