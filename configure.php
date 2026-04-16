<?php

class CacheConfigurator
{
    private $dataDir = __DIR__ . '/data';

    private $cacheDir = __DIR__ . '/.cache';

    private $whiteListFile;

    private $blackListFile;

    public function __construct()
    {
        $this->whiteListFile = $this->dataDir . '/white.list';
        $this->blackListFile = $this->dataDir . '/black.list';

        $this->ensureDirs();
    }

    private function ensureDirs()
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
            echo "Created {$this->cacheDir}\n";
        }
    }

    private function getCacheFileName($type, $firstChar)
    {
        if (is_numeric($firstChar)) {
            return "{$this->cacheDir}/{$type}-others.list";
        }

        return "{$this->cacheDir}/{$type}-" . strtolower($firstChar) . ".list";
    }

    private function buildCache($sourceFile, $cacheType)
    {
        if (!file_exists($sourceFile)) {
            echo "Source file not found: {$sourceFile}\n";
            return false;
        }

        $this->clearCacheType($cacheType);

        $domains = file($sourceFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($domains)) {
            echo "No domains found in {$sourceFile}\n";
            return false;
        }

        $grouped = [];
        foreach ($domains as $domain) {
            $domain = strtolower(trim($domain));
            $firstChar = $domain[0] ?? '';
            $groupName = is_numeric($firstChar) ? 'others' : $firstChar;

            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [];
            }

            $grouped[$groupName][] = $domain;
        }

        $count = 0;
        foreach ($grouped as $prefix => $domainList) {
            $cacheFile = $this->getCacheFileName($cacheType, $prefix);
            sort($domainList);

            $domainList = array_unique($domainList);

            $content = implode(PHP_EOL, $domainList) . PHP_EOL;

            if (file_put_contents($cacheFile, $content, LOCK_EX)) {
                $count += count($domainList);
                echo "Created cache: {$cacheFile} (" . count($domainList) . " domains)\n";
            } else {
                echo "Failed to write: {$cacheFile}\n";
                return false;
            }
        }

        echo "Total domains in {$cacheType}: {$count}\n";
        return true;
    }

    private function clearCacheType($cacheType)
    {
        $pattern = "{$this->cacheDir}/{$cacheType}-*.list";
        $files = glob($pattern);

        foreach ($files as $file) {
            if (unlink($file)) {
                echo "Deleted old cache: {$file}\n";
            }
        }
    }

    public function buildAllCaches()
    {
        echo "========================================\n";
        echo "Building Cache Configuration\n";
        echo "========================================\n\n";

        echo "Building WHITELIST cache from {$this->whiteListFile}...\n";
        $whiteResult = $this->buildCache($this->whiteListFile, 'whitelist');

        echo PHP_EOL;

        echo "Building BLACKLIST cache from {$this->blackListFile}...\n";
        $blackResult = $this->buildCache($this->blackListFile, 'blacklist');

        echo "\n========================================\n";
        if ($whiteResult && $blackResult) {
            echo "✓ Cache configuration completed successfully!\n";
            $this->printStats();
        } else {
            echo "✗ Cache configuration failed!\n";
        }
        echo "========================================\n";

        return $whiteResult && $blackResult;
    }

    private function printStats()
    {
        echo "\nCache Statistics:\n";
        echo "-----------------\n";

        $cacheFiles = glob("{$this->cacheDir}/*.list");
        $totalDomains = 0;
        $totalSize = 0;

        foreach ($cacheFiles as $file) {
            $count = count(file($file, FILE_SKIP_EMPTY_LINES));
            $size = filesize($file);
            $totalDomains += $count;
            $totalSize += $size;

            printf(
                "  %-30s %6d domains  %10s\n",
                basename($file),
                $count,
                $this->formatBytes($size)
            );
        }

        echo "-----------------\n";
        printf(
            "  %-30s %6d domains  %10s\n",
            'TOTAL',
            $totalDomains,
            $this->formatBytes($totalSize)
        );
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function verifyCaches()
    {
        echo "Verifying cache files...\n";

        $cacheFiles = glob("{$this->cacheDir}/*.list");
        if (empty($cacheFiles)) {
            echo "No cache files found!\n";
            return false;
        }

        foreach ($cacheFiles as $file) {
            $domains = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            echo "  " . basename($file) . ": " . count($domains) . " domains\n";
        }

        return true;
    }
}

if (php_sapi_name() === 'cli') {
    $configurator = new CacheConfigurator();

    if (isset($argv[1]) && $argv[1] === 'verify') {
        $configurator->verifyCaches();
    } else {
        $configurator->buildAllCaches();
    }
}
