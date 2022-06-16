<?php
declare(strict_types=1);

require_once realpath('vendor/autoload.php');

use App\Enums\Country;
use App\Enums\DrinkType;
use App\Factory\CanDrinkFactory;

function canDrink(DrinkType $drinkType, Country $country, int $age): bool
{
    $countryCanDrink = CanDrinkFactory::createCanDrink($country);

    return $countryCanDrink::handler($age, $drinkType);
}

echo canDrink(DrinkType::CERVEJA, Country::BOLIVIA, 22) ? 'sim' : 'nao';
