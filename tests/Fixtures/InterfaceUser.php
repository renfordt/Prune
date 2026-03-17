<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Fixtures;

interface Greeter
{
    public function greet(): string;
}

class InterfaceUser implements Greeter
{
    public function greet(): string
    {
        return 'hi';
    }
}
