<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TvdbService
{
    private string $apiKey;
    private string $apiUrl;
    private ?string $token = null;

    public function __construct()
    {
        $this->apiKey = config('services.tvdb.api_key');
        $this->apiUrl = config('services.tvdb.api_url', 'https://api4.thetvdb.com/v4');
    }

    /**
     * Get authentication token for TVDB API
     */
    private function getAuthToken(): ?string
    {
        if ($this->token) {
            return $this->token;
        }

        // Check cache first
        $cachedToken = Cache::get('tvdb_auth_token');
        if ($cachedToken) {
            $this->token = $cachedToken;
            return $this->token;
        }

        if (!$this->apiKey) {
            Log::warning('TVDB API key not configured');
            return null;
        }

        try {
            $response = Http::post("{$this->apiUrl}/login", [
                'apikey' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->token = $data['data']['token'] ?? null;
                
                if ($this->token) {
                    // Cache token for 23 hours (tokens expire in 24 hours)
                    Cache::put('tvdb_auth_token', $this->token, now()->addHours(23));
                }
                
                return $this->token;
            }
        } catch (\Exception $e) {
            Log::error('Failed to authenticate with TVDB API', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Fetch series artwork from TVDB
     */
    public function getSeriesArtwork(string $tvdbId): ?array
    {
        $token = $this->getAuthToken();
        if (!$token) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json'
            ])->get("{$this->apiUrl}/series/{$tvdbId}/artworks", [
                'type' => 'poster'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $artworks = $data['data'] ?? [];
                
                if (!empty($artworks)) {
                    // Get the first poster artwork
                    $artwork = $artworks[0];
                    return [
                        'poster_url' => "https://artworks.thetvdb.com{$artwork['image']}",
                        'source' => 'tvdb'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch TVDB series artwork', [
                'tvdb_id' => $tvdbId,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Fetch episode artwork from TVDB
     */
    public function getEpisodeArtwork(string $tvdbId): ?array
    {
        $token = $this->getAuthToken();
        if (!$token) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json'
            ])->get("{$this->apiUrl}/episodes/{$tvdbId}/extended");

            if ($response->successful()) {
                $data = $response->json();
                $episode = $data['data'] ?? [];
                
                if (isset($episode['image'])) {
                    return [
                        'poster_url' => "https://artworks.thetvdb.com{$episode['image']}",
                        'source' => 'tvdb'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch TVDB episode artwork', [
                'tvdb_id' => $tvdbId,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }
}