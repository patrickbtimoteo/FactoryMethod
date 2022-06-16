<?php

namespace App\Entities;

use App\Entities\Interfaces\CanDrink;
use App\Enums\DrinkType;

class CanDrinkBrasil implements CanDrink
{
    const AGE_MAX = 18;

    public static function handler(int $age, DrinkType $drinkType): bool
    {
        return $age >= self::AGE_MAX;
    }
}
