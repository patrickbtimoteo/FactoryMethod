<?php

namespace Tests\Unit\Entities;

use App\Entities\CanDrinkLibya;
use App\Entities\Interfaces\CanDrink;
use App\Enums\DrinkType;
use PHPUnit\Framework\TestCase;

class CanDrinkLibyaTest extends TestCase
{
    private CanDrink $canDrink;

    protected function setUp(): void
    {
        parent::setUp();

        $this->canDrink = new CanDrinkLibya();
    }

    public function testSuccess(): void
    {
        $assert = $this->canDrink::handler(1001, DrinkType::CERVEJA);

        $this->assertTrue($assert);
    }

    public function testFailure(): void
    {
        $assert = $this->canDrink::handler(16, DrinkType::CERVEJA);

        $this->assertFalse($assert);
    }
}