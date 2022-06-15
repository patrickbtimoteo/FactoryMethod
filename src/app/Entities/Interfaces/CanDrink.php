<?php

namespace App\Entities\Interfaces;

use App\Enums\DrinkType;

interface CanDrink
{
    public static function handler(int $age, DrinkType $drinkType): bool;
}
