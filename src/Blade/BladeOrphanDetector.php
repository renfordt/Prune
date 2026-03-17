<?php

declare(strict_types=1);

namespace Renfordt\Prune\Blade;

class BladeOrphanDetector
{
    /**
     * @param  list<BladeEntry>  $bladeViews
     * @param  list<string>  $references
     * @param  list<string>  $excludeViews
     * @return list<BladeEntry>
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

            // Also check component-style reference for views in components/ directory
            $componentName = $this->viewNameToComponentTag($entry->viewName);
            if ($componentName !== null && isset($referencedSet[$componentName])) {
                continue;
            }

            $orphans[] = $entry;
        }

        usort($orphans, fn (BladeEntry $a, BladeEntry $b): int => $a->viewName <=> $b->viewName);

        return $orphans;
    }

    private function viewNameToComponentTag(string $viewName): ?string
    {
        if (! str_starts_with($viewName, 'components.')) {
            return null;
        }

        return $viewName;
    }
}
