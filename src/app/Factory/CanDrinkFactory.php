<?php

namespace App\Factory;

use App\Entities\CanDrinkArgentina;
use App\Entities\CanDrinkBolivia;
use App\Entities\CanDrinkBrasil;
use App\Entities\CanDrinkLibya;
use App\Entities\Interfaces\CanDrink;
use App\Enums\Country;

class CanDrinkFactory
{
    public static function createCanDrink(Country $country): CanDrink
    {
        switch ($country) {
            case Country::BRASIL:
                return new CanDrinkBrasil();

            case Country::BOLIVIA:
                return new CanDrinkBolivia();

            case Country::ARGENTINA:
                return new CanDrinkArgentina();

            case Country::LIBYA:
                return new CanDrinkLibya();

            default:
                new Exception('Nenhum pais encontrado');
        }
    }
}
