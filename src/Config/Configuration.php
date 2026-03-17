<?php

declare(strict_types=1);

namespace Renfordt\Prune\Config;

use Nette\Neon\Neon;

class Configuration
{
    /**
     * @param  list<string>  $paths
     * @param  list<string>  $excludePaths
     * @param  list<string>  $extensions
     * @param  list<string>  $bladeViewPaths
     * @param  list<string>  $bladeExcludeViews
     */
    public function __construct(
        public readonly array $paths = ['src'],
        public readonly array $excludePaths = ['vendor'],
        public readonly array $extensions = ['php'],
        public readonly string $format = 'console',
        public readonly bool $bladeEnabled = true,
        public readonly array $bladeViewPaths = ['resources/views'],
        public readonly array $bladeExcludeViews = [],
    ) {
    }

    public static function load(string $workingDir, ?string $configFile = null): self
    {
        $configPath = self::resolveConfigPath($workingDir, $configFile);

        if ($configPath === null) {
            return new self();
        }

        $content = file_get_contents($configPath);
        if ($content === false) {
            return new self();
        }

        /** @var array{parameters?: array<string, mixed>} $parsed */
        $parsed = Neon::decode($content);
        $params = $parsed['parameters'] ?? [];

        /** @var list<string> $paths */
        $paths = $params['paths'] ?? ['src'];
        /** @var list<string> $excludePaths */
        $excludePaths = $params['excludePaths'] ?? ['vendor'];
        /** @var list<string> $extensions */
        $extensions = $params['extensions'] ?? ['php'];
        /** @var string $format */
        $format = $params['format'] ?? 'console';

        /** @var array<string, mixed> $blade */
        $blade = $params['blade'] ?? [];
        /** @var bool $bladeEnabled */
        $bladeEnabled = $blade['enabled'] ?? true;
        /** @var list<string> $bladeViewPaths */
        $bladeViewPaths = $blade['viewPaths'] ?? ['resources/views'];
        /** @var list<string> $bladeExcludeViews */
        $bladeExcludeViews = $blade['excludeViews'] ?? [];

        return new self(
            paths: $paths,
            excludePaths: $excludePaths,
            extensions: $extensions,
            format: $format,
            bladeEnabled: $bladeEnabled,
            bladeViewPaths: $bladeViewPaths,
            bladeExcludeViews: $bladeExcludeViews,
        );
    }

    private static function resolveConfigPath(string $workingDir, ?string $configFile): ?string
    {
        if ($configFile !== null) {
            $path = realpath($configFile);

            return $path !== false ? $path : null;
        }

        $candidates = [
            $workingDir . '/prune.neon',
            $workingDir . '/prune.neon.dist',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
