<?php

namespace Tests\Unit\Factory;

use App\Entities\CanDrinkArgentina;
use App\Entities\CanDrinkBolivia;
use App\Entities\CanDrinkBrasil;
use App\Entities\CanDrinkLibya;
use App\Enums\Country;
use App\Factory\CanDrinkFactory;
use PHPUnit\Framework\TestCase;

class CanDrinkFactoryTest extends TestCase
{
    private canDrinkFactory $canDrinkFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->canDrinkFactory = new CanDrinkFactory();
    }

    public function testCanDrinkArgentina(): void
    {
        $return = $this->canDrinkFactory::createCanDrink(Country::ARGENTINA);
        $this->assertInstanceOf(CanDrinkArgentina::class , $return);
    }

    public function testCanDrinkBrasil(): void
    {
        $return = $this->canDrinkFactory::createCanDrink(Country::BRASIL);
        $this->assertInstanceOf(CanDrinkBrasil::class , $return);
    }

    public function testCanDrinkBolivia(): void
    {
        $return = $this->canDrinkFactory::createCanDrink(Country::BOLIVIA);
        $this->assertInstanceOf(CanDrinkBolivia::class , $return);
    }

    public function testCanDrinkLibya(): void
    {
        $return = $this->canDrinkFactory::createCanDrink(Country::LIBYA);
        $this->assertInstanceOf(CanDrinkLibya::class , $return);
    }
}