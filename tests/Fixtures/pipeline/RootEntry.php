<?php

declare(strict_types=1);

namespace Renfordt\Prune\Tests\Fixtures\Pipeline;

class RootEntry
{
    public function build(): ReferencedClass
    {
        return new ReferencedClass();
    }
}
