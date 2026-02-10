<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use ZipArchive;

class DsymUuidExtractor
{
    public function extract(string $filePath): ?string
    {
        if (!is_file($filePath)) {
            return null;
        }

        $tempDir = null;
        $targetPath = $filePath;

        if ($this->isZipFile($filePath)) {
            if (!class_exists(ZipArchive::class)) {
                return null;
            }

            $tempDir = storage_path('app/tmp/dsym_' . Str::uuid());
            File::makeDirectory($tempDir, 0755, true);

            $zip = new ZipArchive();
            if ($zip->open($filePath) !== true) {
                File::deleteDirectory($tempDir);
                return null;
            }

            $zip->extractTo($tempDir);
            $zip->close();

            $bundlePath = $this->findDsymBundle($tempDir);
            $targetPath = $bundlePath ?? $tempDir;
        }

        $uuid = $this->runDwarfdump($targetPath);

        if ($tempDir) {
            File::deleteDirectory($tempDir);
        }

        return $uuid;
    }

    private function isZipFile(string $filePath): bool
    {
        return Str::endsWith(strtolower($filePath), '.zip');
    }

    private function findDsymBundle(string $root): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir() && Str::endsWith($item->getFilename(), '.dSYM')) {
                return $item->getPathname();
            }
        }

        return null;
    }

    private function runDwarfdump(string $targetPath): ?string
    {
        $commands = [
            ['xcrun', 'dwarfdump', '--uuid', $targetPath],
            ['dwarfdump', '--uuid', $targetPath],
            ['llvm-dwarfdump', '--uuid', $targetPath],
        ];

        foreach ($commands as $command) {
            $process = new Process($command);
            $process->setTimeout(10);
            $process->run();

            if (!$process->isSuccessful()) {
                continue;
            }

            $uuid = $this->parseUuid($process->getOutput());
            if ($uuid) {
                return $uuid;
            }
        }

        return null;
    }

    private function parseUuid(string $output): ?string
    {
        if (preg_match('/UUID:\s*([A-F0-9-]+)/i', $output, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }
}
