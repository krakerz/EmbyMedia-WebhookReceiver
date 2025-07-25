<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbyService
{
    private ?string $baseUrl;
    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.emby.base_url');
        $this->apiKey = config('services.emby.api_key');
    }

    /**
     * Get cover image URL from Emby server using item data
     */
    public function getCoverImageFromItem(array $itemData): ?array
    {
        if (!$this->baseUrl) {
            Log::info('Emby base URL not configured, skipping Emby image fetch');
            return null;
        }

        // Extract image information from item data
        $itemId = $itemData['Id'] ?? null;
        $imageTags = $itemData['ImageTags'] ?? [];
        $primaryImageTag = $imageTags['Primary'] ?? null;

        if (!$itemId || !$primaryImageTag) {
            Log::info('Missing item ID or primary image tag for Emby image fetch', [
                'item_id' => $itemId,
                'has_primary_tag' => !empty($primaryImageTag)
            ]);
            return null;
        }

        // Build the image URL
        $imageUrl = $this->buildImageUrl($itemId, $primaryImageTag);
        
        if ($imageUrl) {
            Log::info('Successfully generated Emby image URL', [
                'item_id' => $itemId,
                'image_url' => $imageUrl
            ]);

            return [
                'poster_url' => $imageUrl,
                'source' => 'emby'
            ];
        }

        return null;
    }

    /**
     * Get backdrop image URL from Emby server
     */
    public function getBackdropImageFromItem(array $itemData): ?string
    {
        if (!$this->baseUrl) {
            return null;
        }

        $itemId = $itemData['Id'] ?? null;
        $backdropImageTags = $itemData['BackdropImageTags'] ?? [];

        if (!$itemId || empty($backdropImageTags)) {
            return null;
        }

        // Use the first backdrop image
        $backdropTag = $backdropImageTags[0];
        return $this->buildImageUrl($itemId, $backdropTag, 'Backdrop');
    }

    /**
     * Get series poster for episodes using parent series data
     */
    public function getSeriesImageFromItem(array $itemData): ?array
    {
        if (!$this->baseUrl) {
            return null;
        }

        // For episodes, try to get series poster
        $seriesId = $itemData['SeriesId'] ?? null;
        $seriesPrimaryImageTag = $itemData['SeriesPrimaryImageTag'] ?? null;

        if (!$seriesId || !$seriesPrimaryImageTag) {
            Log::info('Missing series ID or series primary image tag', [
                'series_id' => $seriesId,
                'has_series_primary_tag' => !empty($seriesPrimaryImageTag)
            ]);
            return null;
        }

        $imageUrl = $this->buildImageUrl($seriesId, $seriesPrimaryImageTag);
        
        if ($imageUrl) {
            Log::info('Successfully generated Emby series image URL', [
                'series_id' => $seriesId,
                'image_url' => $imageUrl
            ]);

            return [
                'poster_url' => $imageUrl,
                'source' => 'emby_series'
            ];
        }

        return null;
    }

    /**
     * Build image URL for Emby server
     */
    private function buildImageUrl(string $itemId, string $imageTag, string $imageType = 'Primary'): string
    {
        $baseUrl = rtrim($this->baseUrl, '/');
        return "{$baseUrl}/emby/Items/{$itemId}/Images/{$imageType}?tag={$imageTag}&quality=90";
    }

    /**
     * Verify if Emby server is accessible
     */
    public function verifyConnection(): bool
    {
        if (!$this->baseUrl) {
            return false;
        }

        try {
            $url = rtrim($this->baseUrl, '/') . '/emby/System/Info/Public';
            $response = Http::timeout(5)->get($url);
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Failed to verify Emby server connection', [
                'base_url' => $this->baseUrl,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get additional metadata from Emby server if API key is provided
     */
    public function getItemMetadata(string $itemId): ?array
    {
        if (!$this->baseUrl || !$this->apiKey) {
            return null;
        }

        try {
            $url = rtrim($this->baseUrl, '/') . "/emby/Items/{$itemId}";
            $response = Http::timeout(10)
                ->withHeaders(['X-Emby-Token' => $this->apiKey])
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch additional metadata from Emby', [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }
}