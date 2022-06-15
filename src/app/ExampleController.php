<?php

namespace App;

class ExampleController
{
    public function __construct(
        private string $name
    ) {
    }

    public function getName(): int
    {
        return (
            1 + 5
        );
    }
}
