<?php

declare(strict_types=1);

namespace Renfordt\Prune\Analyzer;

final readonly class ClassEntry
{
    public function __construct(
        public string $fqcn,
        public string $file,
        public int $line,
    ) {
    }
}
