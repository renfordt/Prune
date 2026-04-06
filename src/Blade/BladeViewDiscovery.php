<?php

declare(strict_types=1);

namespace Renfordt\Prune\Blade;

use Symfony\Component\Finder\Finder;

class BladeViewDiscovery
{
    /**
     * @param  list<string>  $viewPaths
     * @return list<BladeEntry>
     */
    public function discover(array $viewPaths): array
    {
        $existingPaths = array_values(array_filter($viewPaths, is_dir(...)));

        if ($existingPaths === []) {
            return [];
        }

        // Sort by path length descending so more specific paths match first
        // in pathToViewName(). This prevents a parent like resources/ from
        // matching before resources/views/ and producing wrong view names
        // (e.g. "views.about" instead of "about").
        usort($existingPaths, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $finder = new Finder();
        $finder->files()->in($existingPaths)->name('*.blade.php')->sortByName();

        $entries = [];
        foreach ($finder as $file) {
            $realPath = $file->getRealPath();
            if ($realPath === false) {
                continue;
            }

            // Deduplicate: overlapping discovery paths can find the same file twice
            if (isset($entries[$realPath])) {
                continue;
            }

            $viewName = $this->pathToViewName($realPath, $existingPaths);
            if ($viewName === null) {
                continue;
            }

            $entries[$realPath] = new BladeEntry($viewName, $realPath);
        }

        return array_values($entries);
    }

    /**
     * @param  list<string>  $viewPaths
     */
    private function pathToViewName(string $filePath, array $viewPaths): ?string
    {
        foreach ($viewPaths as $basePath) {
            $realBase = realpath($basePath);
            if ($realBase === false) {
                continue;
            }

            if (! str_starts_with($filePath, $realBase . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $relative = substr($filePath, strlen($realBase) + 1);
            $relative = preg_replace('/\.blade\.php$/', '', $relative);
            if ($relative === null) {
                continue;
            }

            // Strip the Livewire 4 / Volt anonymous-component prefix (⚡) from each path segment.
            // Files like ⚡counter.blade.php are referenced in code without the prefix.
            $viewName = str_replace(DIRECTORY_SEPARATOR, '.', $relative);

            return str_replace('⚡', '', $viewName);
        }

        return null;
    }
}
