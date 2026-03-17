<?php

declare(strict_types=1);

namespace Renfordt\Prune\Output;

use Renfordt\Prune\Analyzer\ClassEntry;
use Renfordt\Prune\Blade\BladeEntry;

final readonly class Report
{
    /**
     * @param  list<ClassEntry>  $classOrphans
     * @param  list<BladeEntry>  $bladeOrphans
     */
    public function __construct(
        public array $classOrphans = [],
        public array $bladeOrphans = [],
    ) {
    }

    public function hasOrphans(): bool
    {
        return $this->classOrphans !== [] || $this->bladeOrphans !== [];
    }
}
