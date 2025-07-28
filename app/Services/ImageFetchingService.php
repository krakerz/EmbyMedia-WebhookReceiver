<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ImageFetchingService
{
    private TvdbService $tvdbService;
    private ImdbService $imdbService;
    private EmbyService $embyService;

    public function __construct(TvdbService $tvdbService, ImdbService $imdbService, EmbyService $embyService)
    {
        $this->tvdbService = $tvdbService;
        $this->imdbService = $imdbService;
        $this->embyService = $embyService;
    }

    /**
     * Fetch cover image for media item using multiple provider sources
     * 
     * Attempts to fetch cover images in priority order:
     * 1. Emby server (using item data)
     * 2. TVDB (for TV content)
     * 3. TMDB (using IMDB ID)
     * 4. TMDB search (by title and year)
     * 
     * @param array $metadata Extracted metadata from webhook
     * @param string $itemType Type of media (Movie, Episode, etc.)
     * @param string $itemName Name/title of the media item
     * @param array $rawItem Raw item data from Emby webhook
     * @return array|null Array with image data and source, or null if no image found
     */
    public function fetchCoverImage(array $metadata, string $itemType, string $itemName, array $rawItem = []): ?array
    {
        $providerIds = $metadata['provider_ids'] ?? [];
        $year = $metadata['year'] ?? null;
        
        Log::info('Fetching cover image', [
            'item_name' => $itemName,
            'item_type' => $itemType,
            'provider_ids' => $providerIds
        ]);

        // Try Emby server first if we have item data
        if (!empty($rawItem)) {
            $embyImage = $this->embyService->getCoverImageFromItem($rawItem);
            if ($embyImage) {
                Log::info('Successfully fetched image from Emby server', [
                    'item_name' => $itemName,
                    'item_id' => $rawItem['Id'] ?? 'unknown'
                ]);
                return $embyImage;
            }

            // For episodes, try to get series poster from Emby
            if ($itemType === 'Episode') {
                $seriesImage = $this->embyService->getSeriesImageFromItem($rawItem);
                if ($seriesImage) {
                    Log::info('Successfully fetched series image from Emby server', [
                        'item_name' => $itemName,
                        'series_id' => $rawItem['SeriesId'] ?? 'unknown'
                    ]);
                    return $seriesImage;
                }
            }
        }

        // Try TVDB for TV content as fallback
        if (in_array($itemType, ['Episode', 'Season', 'Series']) && isset($providerIds['Tvdb'])) {
            $tvdbId = $providerIds['Tvdb'];
            
            if ($itemType === 'Episode') {
                $artwork = $this->tvdbService->getEpisodeArtwork($tvdbId);
            } else {
                $artwork = $this->tvdbService->getSeriesArtwork($tvdbId);
            }
            
            if ($artwork) {
                Log::info('Successfully fetched image from TVDB', ['tvdb_id' => $tvdbId]);
                return $artwork;
            }
        }

        // Try IMDB/TMDB as fallback
        if (isset($providerIds['IMDB'])) {
            $imdbId = $providerIds['IMDB'];
            
            if (in_array($itemType, ['Episode', 'Season', 'Series'])) {
                $artwork = $this->imdbService->getTvShowPoster($imdbId);
            } else {
                $artwork = $this->imdbService->getMoviePoster($imdbId);
            }
            
            if ($artwork) {
                Log::info('Successfully fetched image from TMDB using IMDB ID', ['imdb_id' => $imdbId]);
                return $artwork;
            }
        }

        // Final fallback: search by title and year
        if ($itemName) {
            $searchType = in_array($itemType, ['Episode', 'Season', 'Series']) ? 'tv' : 'movie';
            $artwork = $this->imdbService->searchByTitle($itemName, $year, $searchType);
            
            if ($artwork) {
                Log::info('Successfully fetched image from TMDB by title search', [
                    'title' => $itemName,
                    'year' => $year,
                    'type' => $searchType
                ]);
                return $artwork;
            }
        }

        Log::warning('No cover image found for media item', [
            'item_name' => $itemName,
            'item_type' => $itemType,
            'provider_ids' => $providerIds
        ]);

        return null;
    }

    /**
     * Fetch series poster for episodes using series data
     */
    public function fetchSeriesPosterForEpisode(array $metadata): ?array
    {
        $providerIds = $metadata['provider_ids'] ?? [];
        $seriesName = $metadata['series_name'] ?? null;
        $year = $metadata['year'] ?? null;

        // Try TVDB first if we have series TVDB ID
        if (isset($providerIds['Tvdb'])) {
            $artwork = $this->tvdbService->getSeriesArtwork($providerIds['Tvdb']);
            if ($artwork) {
                return $artwork;
            }
        }

        // Try IMDB/TMDB for series
        if (isset($providerIds['IMDB'])) {
            $artwork = $this->imdbService->getTvShowPoster($providerIds['IMDB']);
            if ($artwork) {
                return $artwork;
            }
        }

        // Search by series name
        if ($seriesName) {
            $artwork = $this->imdbService->searchByTitle($seriesName, $year, 'tv');
            if ($artwork) {
                return $artwork;
            }
        }

        return null;
    }
}