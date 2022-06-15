<?php

namespace App\Entities;

use App\Entities\Interfaces\CanDrink;
use App\Enums\DrinkType;

class CanDrinkLibya implements CanDrink
{
    public static function handler(int $age, DrinkType $drinkType): bool
    {
        return false;
    }
}
