<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Assets;

use ScheduledPageReviews\Application\Config;

/**
 * Resolver for Vite's build manifest.
 *
 * Reads dist/.vite/manifest.json once per request and exposes hashed asset
 * URLs for a given entry. Returns null/empty arrays when the manifest is
 * absent so callers can no-op during local development before the first
 * build.
 */
final class ViteManifest
{
    /** @var array<string, array{file: string, css?: list<string>, imports?: list<string>}>|null */
    private ?array $manifest = null;

    /**
     * @return array{file: string, css?: list<string>, imports?: list<string>}|null
     */
    public function getEntry(string $entry): ?array
    {
        $manifest = $this->load();
        return $manifest[$entry] ?? null;
    }

    public function getEntryUrl(string $entry): ?string
    {
        $entryData = $this->getEntry($entry);
        if ($entryData === null) {
            return null;
        }
        return $this->distUrl() . '/' . ltrim($entryData['file'], '/');
    }

    /**
     * @return list<string>
     */
    public function getEntryCssUrls(string $entry): array
    {
        $entryData = $this->getEntry($entry);
        if ($entryData === null || empty($entryData['css'])) {
            return [];
        }
        return array_values(array_map(
            fn (string $file): string => $this->distUrl() . '/' . ltrim($file, '/'),
            $entryData['css']
        ));
    }

    public function distUrl(): string
    {
        $pluginFile = (string) Config::get('paths', 'plugin_file');
        return plugin_dir_url($pluginFile) . 'dist';
    }

    /**
     * @return array<string, array{file: string, css?: list<string>, imports?: list<string>}>
     */
    private function load(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $manifestPath = ((string) Config::get('paths', 'dist_dir')) . '/.vite/manifest.json';

        if (!is_file($manifestPath)) {
            $this->manifest = [];
            return $this->manifest;
        }

        $contents = file_get_contents($manifestPath);
        if ($contents === false) {
            $this->manifest = [];
            return $this->manifest;
        }

        $decoded = json_decode($contents, true);
        $this->manifest = is_array($decoded) ? $decoded : [];

        return $this->manifest;
    }
}
