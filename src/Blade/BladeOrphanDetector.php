<?php

declare(strict_types=1);

namespace Renfordt\Prune\Blade;

class BladeOrphanDetector
{
    /**
     * Detects and returns a list of orphan Blade views.
     *
     * @param list<BladeEntry> $bladeViews An array of BladeEntry objects representing all available Blade views.
     * @param list<string> $references An array of view names that are considered referenced.
     * @param list<string> $excludeViews An optional array of view names to be excluded from detection.
     * @return list<BladeEntry> An array of BladeEntry objects that are neither referenced nor excluded, sorted by view name.
     */
    public function detect(array $bladeViews, array $references, array $excludeViews = []): array
    {
        $referencedSet = array_flip($references);
        $excludedSet = array_flip($excludeViews);

        $orphans = [];
        foreach ($bladeViews as $entry) {
            if (isset($referencedSet[$entry->viewName])) {
                continue;
            }
            if (isset($excludedSet[$entry->viewName])) {
                continue;
            }

            $orphans[] = $entry;
        }

        usort($orphans, fn (BladeEntry $a, BladeEntry $b): int => $a->viewName <=> $b->viewName);

        return $orphans;
    }
}
