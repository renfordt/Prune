<?php

declare(strict_types=1);

namespace Renfordt\Prune\Analyzer;

class OrphanDetector
{
    /**
     * Identifies and returns a list of orphaned classes based on the provided class map and references.
     *
     * @param array<string, ClassEntry>  $classMap An associative array where the keys are fully qualified class names (FQCNs)
     *                        and the values are their associated entries.
     * @param list<string> $references An array containing FQCNs that are referenced.
     *
     * @return list<ClassEntry> An array of entries from the class map that are not referenced in the given references.
     */
    public function detect(array $classMap, array $references): array
    {
        $orphans = array_values(array_diff_key($classMap, array_flip($references)));

        usort($orphans, fn (ClassEntry $a, ClassEntry $b): int => $a->file <=> $b->file);

        return $orphans;
    }
}
