<?php

require_once realpath('vendor/autoload.php');

use App\Enums\Country;
use App\Enums\DrinkType;

function canOrNotDrinkInBrasil(int $age, DrinkType $drinkType): bool
{
    return $age >= 18;
}

function canOrNotDrinkInLibya(int $age, DrinkType $drinkType): bool
{
    return false;
}

function canDrink(DrinkType $drinkType, Country $country, int $age): bool
{
    switch ($country){
        case Country::BRASIL:
            return canOrNotDrinkInBrasil($age, $drinkType);

        case Country::ARGENTINA:
            return $age >= 19;

        case Country::LIBYA:
            return canOrNotDrinkInLibya($age, $drinkType);

        default: new Exception('Nenhum pais encontrado');
    }

    return false;
}


echo canDrink(DrinkType::CERVEJA , Country::LIBYA , 100) ? 'sim' : 'nao';