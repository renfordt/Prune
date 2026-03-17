<?php

declare(strict_types=1);

namespace Renfordt\Prune\Blade;

final readonly class BladeEntry
{
    public function __construct(
        public string $viewName,
        public string $file,
    ) {
    }
}
