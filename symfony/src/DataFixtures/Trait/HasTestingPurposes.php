<?php

namespace App\DataFixtures\Trait;

trait HasTestingPurposes
{
    public static function getGroups(): array
    {
        return ['test'];
    }
}
