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

        $finder = new Finder();
        $finder->files()->in($existingPaths)->name('*.blade.php')->sortByName();

        $entries = [];
        foreach ($finder as $file) {
            $realPath = $file->getRealPath();
            if ($realPath === false) {
                continue;
            }

            $viewName = $this->pathToViewName($realPath, $existingPaths);
            if ($viewName === null) {
                continue;
            }

            $entries[] = new BladeEntry($viewName, $realPath);
        }

        return $entries;
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

            return str_replace(DIRECTORY_SEPARATOR, '.', $relative);
        }

        return null;
    }
}
