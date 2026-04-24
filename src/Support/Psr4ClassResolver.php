<?php

namespace Shelfwood\N8n\Support;

/**
 * Resolves a file path to its fully qualified class name using the host
 * application's composer.json PSR-4 autoload map.
 */
class Psr4ClassResolver
{
    /** @var array<string, string>|null normalised namespace => relative path (with trailing slash) */
    private static ?array $cachedMap = null;

    /**
     * Resolve a file path (absolute or relative to base path) to an FQCN.
     *
     * @param  string  $path  absolute file path
     * @param  string  $basePath  application base path
     */
    public static function resolve(string $path, string $basePath): ?string
    {
        $relative = str_replace(rtrim($basePath, '/').'/', '', $path);
        $relative = preg_replace('/\.php$/', '', $relative);

        if ($relative === null) {
            return null;
        }

        $map = self::loadMap($basePath);

        // longest-prefix-wins
        $bestPrefix = null;
        $bestNamespace = null;

        foreach ($map as $namespace => $prefix) {
            if (str_starts_with($relative, $prefix)) {
                if ($bestPrefix === null || strlen($prefix) > strlen($bestPrefix)) {
                    $bestPrefix = $prefix;
                    $bestNamespace = $namespace;
                }
            }
        }

        if ($bestPrefix === null) {
            return null;
        }

        $remainder = substr($relative, strlen($bestPrefix));

        return $bestNamespace.str_replace('/', '\\', $remainder);
    }

    /**
     * Load and cache the PSR-4 map from the host composer.json.
     *
     * @return array<string, string>
     */
    private static function loadMap(string $basePath): array
    {
        if (self::$cachedMap !== null) {
            return self::$cachedMap;
        }

        $composerPath = rtrim($basePath, '/').'/composer.json';

        if (! is_file($composerPath)) {
            return self::$cachedMap = [];
        }

        $decoded = json_decode((string) file_get_contents($composerPath), true);

        if (! is_array($decoded)) {
            return self::$cachedMap = [];
        }

        $psr4 = array_merge(
            $decoded['autoload']['psr-4'] ?? [],
            $decoded['autoload-dev']['psr-4'] ?? [],
        );

        $map = [];

        foreach ($psr4 as $namespace => $paths) {
            foreach ((array) $paths as $p) {
                $normalisedPath = rtrim($p, '/').'/';
                $map[$namespace] = $normalisedPath;
            }
        }

        return self::$cachedMap = $map;
    }

    /**
     * Reset cached PSR-4 map. Intended for tests.
     */
    public static function flush(): void
    {
        self::$cachedMap = null;
    }
}
