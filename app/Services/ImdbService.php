<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImdbService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.imdb.api_key');
        $this->apiUrl = config('services.imdb.api_url', 'https://api.themoviedb.org/3');
    }

    /**
     * Fetch movie poster from TMDB using IMDB ID
     */
    public function getMoviePoster(string $imdbId): ?array
    {
        if (!$this->apiKey) {
            Log::warning('TMDB API key not configured');
            return null;
        }

        try {
            // Find movie by IMDB ID
            $response = Http::get("{$this->apiUrl}/find/{$imdbId}", [
                'api_key' => $this->apiKey,
                'external_source' => 'imdb_id'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $movies = $data['movie_results'] ?? [];
                
                if (!empty($movies)) {
                    $movie = $movies[0];
                    if (isset($movie['poster_path'])) {
                        return [
                            'poster_url' => "https://image.tmdb.org/t/p/w500{$movie['poster_path']}",
                            'backdrop_url' => isset($movie['backdrop_path']) ? "https://image.tmdb.org/t/p/w1280{$movie['backdrop_path']}" : null,
                            'source' => 'tmdb'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch TMDB movie poster', [
                'imdb_id' => $imdbId,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Fetch TV show poster from TMDB using IMDB ID
     */
    public function getTvShowPoster(string $imdbId): ?array
    {
        if (!$this->apiKey) {
            Log::warning('TMDB API key not configured');
            return null;
        }

        try {
            // Find TV show by IMDB ID
            $response = Http::get("{$this->apiUrl}/find/{$imdbId}", [
                'api_key' => $this->apiKey,
                'external_source' => 'imdb_id'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $tvShows = $data['tv_results'] ?? [];
                
                if (!empty($tvShows)) {
                    $tvShow = $tvShows[0];
                    if (isset($tvShow['poster_path'])) {
                        return [
                            'poster_url' => "https://image.tmdb.org/t/p/w500{$tvShow['poster_path']}",
                            'backdrop_url' => isset($tvShow['backdrop_path']) ? "https://image.tmdb.org/t/p/w1280{$tvShow['backdrop_path']}" : null,
                            'source' => 'tmdb'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch TMDB TV show poster', [
                'imdb_id' => $imdbId,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Search for media by title and year
     */
    public function searchByTitle(string $title, ?int $year = null, string $type = 'movie'): ?array
    {
        if (!$this->apiKey) {
            Log::warning('TMDB API key not configured');
            return null;
        }

        try {
            $endpoint = $type === 'tv' ? 'search/tv' : 'search/movie';
            $params = [
                'api_key' => $this->apiKey,
                'query' => $title
            ];

            if ($year) {
                $params[$type === 'tv' ? 'first_air_date_year' : 'year'] = $year;
            }

            $response = Http::get("{$this->apiUrl}/{$endpoint}", $params);

            if ($response->successful()) {
                $data = $response->json();
                $results = $data['results'] ?? [];
                
                if (!empty($results)) {
                    $result = $results[0];
                    if (isset($result['poster_path'])) {
                        return [
                            'poster_url' => "https://image.tmdb.org/t/p/w500{$result['poster_path']}",
                            'backdrop_url' => isset($result['backdrop_path']) ? "https://image.tmdb.org/t/p/w1280{$result['backdrop_path']}" : null,
                            'source' => 'tmdb'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to search TMDB by title', [
                'title' => $title,
                'year' => $year,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }
}