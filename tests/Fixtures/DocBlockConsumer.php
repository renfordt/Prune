<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Fixtures;

class DocBlockConsumer
{
    /** @var UsedClass */
    private readonly mixed $used;

    /**
     * @param \Renfordt\Prune\Tests\Fixtures\OrphanedClass $orphan
     */
    public function doSomething(mixed $orphan): void
    {
    }

    /** @return UsedClass */
    public function getUsed(): mixed
    {
        return $this->used;
    }

    /** @throws \Renfordt\Prune\Tests\Fixtures\UsedClass */
    public function risky(): void
    {
    }
}
