<?php

namespace App\Entities;

use App\Entities\Interfaces\CanDrink;
use App\Enums\DrinkType;

class CanDrinkArgentina implements CanDrink
{
    const AGE_MAX = 17;

    public static function handler(int $age, DrinkType $drinkType): bool
    {
        return $age >= self::AGE_MAX;
    }
}
