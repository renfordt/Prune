<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Fixtures;

class ConsumerClass
{
    public function run(): string
    {
        $used = new UsedClass();

        return $used->greet();
    }
}
