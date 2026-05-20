<?php

declare(strict_types=1);

namespace Renfordt\Prune\Config;

use Nette\Neon\Neon;
use RuntimeException;

class Configuration
{
    /**
     * @param  list<string>  $paths
     * @param  list<string>  $excludePaths
     * @param  list<string>  $extensions
     * @param  list<string>  $bladeViewPaths
     * @param  list<string>  $bladeReferencePaths
     * @param  list<string>  $bladeExcludeViews
     */
    public function __construct(
        public readonly array $paths = ['src'],
        public readonly array $excludePaths = ['vendor'],
        public readonly array $extensions = ['php'],
        public readonly string $format = 'console',
        public readonly bool $bladeEnabled = true,
        public readonly array $bladeViewPaths = ['resources/views'],
        public readonly array $bladeReferencePaths = ['routes'],
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

        $parsed = Neon::decode($content);
        if (! is_array($parsed)) {
            throw new RuntimeException(sprintf('Invalid config in %s: expected a NEON map at the top level.', $configPath));
        }

        $params = $parsed['parameters'] ?? [];
        if (! is_array($params)) {
            throw new RuntimeException(sprintf('Invalid config in %s: "parameters" must be a map.', $configPath));
        }

        $paths = self::expectStringList($params, 'paths', ['src'], $configPath);
        $excludePaths = self::expectStringList($params, 'excludePaths', ['vendor'], $configPath);
        $extensions = self::expectStringList($params, 'extensions', ['php'], $configPath);
        $format = self::expectString($params, 'format', 'console', $configPath);

        $blade = $params['blade'] ?? [];
        if (! is_array($blade)) {
            throw new RuntimeException(sprintf('Invalid config in %s: "blade" must be a map.', $configPath));
        }

        $bladeEnabled = self::expectBool($blade, 'enabled', true, $configPath);
        $bladeViewPaths = self::expectStringList($blade, 'viewPaths', ['resources/views'], $configPath);
        $bladeReferencePaths = self::expectStringList($blade, 'referencePaths', ['routes'], $configPath);
        $bladeExcludeViews = self::expectStringList($blade, 'excludeViews', [], $configPath);

        return new self(
            paths: $paths,
            excludePaths: $excludePaths,
            extensions: $extensions,
            format: $format,
            bladeEnabled: $bladeEnabled,
            bladeViewPaths: $bladeViewPaths,
            bladeReferencePaths: $bladeReferencePaths,
            bladeExcludeViews: $bladeExcludeViews,
        );
    }

    /**
     * @param array<mixed, mixed> $params
     * @param list<string> $default
     * @return list<string>
     */
    private static function expectStringList(array $params, string $key, array $default, string $configPath): array
    {
        if (! array_key_exists($key, $params)) {
            return $default;
        }

        $value = $params[$key];
        if (! is_array($value)) {
            throw new RuntimeException(sprintf('Invalid config in %s: "%s" must be a list of strings.', $configPath, $key));
        }

        $result = [];
        foreach ($value as $item) {
            if (! is_string($item)) {
                throw new RuntimeException(sprintf('Invalid config in %s: "%s" must be a list of strings.', $configPath, $key));
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param array<mixed, mixed> $params
     */
    private static function expectString(array $params, string $key, string $default, string $configPath): string
    {
        if (! array_key_exists($key, $params)) {
            return $default;
        }

        $value = $params[$key];
        if (! is_string($value)) {
            throw new RuntimeException(sprintf('Invalid config in %s: "%s" must be a string.', $configPath, $key));
        }

        return $value;
    }

    /**
     * @param array<mixed, mixed> $params
     */
    private static function expectBool(array $params, string $key, bool $default, string $configPath): bool
    {
        if (! array_key_exists($key, $params)) {
            return $default;
        }

        $value = $params[$key];
        if (! is_bool($value)) {
            throw new RuntimeException(sprintf('Invalid config in %s: "%s" must be a boolean.', $configPath, $key));
        }

        return $value;
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
