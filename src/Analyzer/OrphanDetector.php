<?php

declare(strict_types=1);

namespace Renfordt\Prune\Analyzer;

class OrphanDetector
{
    /**
     * @param  array<string, ClassEntry>  $classMap
     * @param  list<string>  $references
     * @return list<ClassEntry>
     */
    public function detect(array $classMap, array $references): array
    {
        $referencedSet = array_flip($references);

        $orphans = [];
        foreach ($classMap as $fqcn => $entry) {
            if (! isset($referencedSet[$fqcn])) {
                $orphans[] = $entry;
            }
        }

        usort($orphans, fn (ClassEntry $a, ClassEntry $b): int => $a->file <=> $b->file);

        return $orphans;
    }
}
