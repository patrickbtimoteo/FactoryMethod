<?php

namespace App\Entities;

use App\Entities\Interfaces\CanDrink;
use App\Enums\DrinkType;

class CanDrinkLibya implements CanDrink
{
    const AGE_MAX = 1000;

    public static function handler(int $age, DrinkType $drinkType): bool
    {
        return $age >= self::AGE_MAX;
    }
}
