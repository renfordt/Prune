<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Fixtures;

class FuncCallConsumer
{
    public function checkExistence(): void
    {
        class_exists(UsedClass::class);
        class_exists(\Renfordt\Prune\Tests\Fixtures\OrphanedClass::class);
    }

    public function checkType(object $obj): void
    {
        is_subclass_of($obj, \Renfordt\Prune\Tests\Fixtures\OrphanedClass::class);
    }

    public function checkHierarchy(): void
    {
        class_implements(UsedClass::class);
        class_parents(\Renfordt\Prune\Tests\Fixtures\OrphanedClass::class);
    }

    public function laravelContainer(): void
    {
        app(\Renfordt\Prune\Tests\Fixtures\UsedClass::class);
    }
}
