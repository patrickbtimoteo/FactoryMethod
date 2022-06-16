<?php

namespace Tests\Unit\Entities;

use App\Entities\Interfaces\CanDrink;
use App\Enums\DrinkType;
use PHPUnit\Framework\TestCase;
use App\Entities\CanDrinkBolivia;

class CanDrinkBoliviaTest extends TestCase
{
    private CanDrink $canDrink;

    protected function setUp(): void
    {
        parent::setUp();

        $this->canDrink = new CanDrinkBolivia();
    }

    public function testSuccess(): void
    {
        $assert = $this->canDrink::handler(21, DrinkType::CERVEJA);

        $this->assertTrue($assert);
    }

    public function testFailure(): void
    {
        $assert = $this->canDrink::handler(10, DrinkType::CERVEJA);

        $this->assertFalse($assert);
    }
}