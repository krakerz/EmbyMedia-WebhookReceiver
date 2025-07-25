<?php

namespace Tests\Feature;

use App\Models\EmbyWebhook;
use App\Services\ImageFetchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImageFetchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_with_tvdb_id_fetches_cover_image(): void
    {
        // Mock TVDB API response
        Http::fake([
            'api4.thetvdb.com/v4/login' => Http::response([
                'data' => ['token' => 'fake-token']
            ], 200),
            'api4.thetvdb.com/v4/series/*/artworks*' => Http::response([
                'data' => [
                    [
                        'image' => '/banners/posters/test-poster.jpg',
                        'type' => 'poster'
                    ]
                ]
            ], 200)
        ]);

        $payload = [
            'Event' => 'library.new',
            'Item' => [
                'Type' => 'Episode',
                'Name' => 'Test Episode',
                'SeriesName' => 'Test Series',
                'ProviderIds' => [
                    'Tvdb' => '123456'
                ],
                'ProductionYear' => 2025,
                'Overview' => 'Test episode overview'
            ],
            'Server' => [
                'Name' => 'Test Server'
            ]
        ];

        $response = $this->postJson('/emby/webhook', $payload);

        $response->assertStatus(200);
        
        $webhook = EmbyWebhook::first();
        $this->assertNotNull($webhook);
        $this->assertArrayHasKey('poster_url', $webhook->metadata);
        $this->assertStringContains('artworks.thetvdb.com', $webhook->metadata['poster_url']);
        $this->assertEquals('tvdb', $webhook->metadata['source']);
    }

    public function test_webhook_with_imdb_id_fetches_cover_image(): void
    {
        // Mock TMDB API response
        Http::fake([
            'api.themoviedb.org/3/find/*' => Http::response([
                'movie_results' => [
                    [
                        'poster_path' => '/test-poster.jpg',
                        'backdrop_path' => '/test-backdrop.jpg'
                    ]
                ]
            ], 200)
        ]);

        $payload = [
            'Event' => 'library.new',
            'Item' => [
                'Type' => 'Movie',
                'Name' => 'Test Movie',
                'ProviderIds' => [
                    'IMDB' => 'tt1234567'
                ],
                'ProductionYear' => 2025,
                'Overview' => 'Test movie overview'
            ],
            'Server' => [
                'Name' => 'Test Server'
            ]
        ];

        $response = $this->postJson('/emby/webhook', $payload);

        $response->assertStatus(200);
        
        $webhook = EmbyWebhook::first();
        $this->assertNotNull($webhook);
        $this->assertArrayHasKey('poster_url', $webhook->metadata);
        $this->assertStringContains('image.tmdb.org', $webhook->metadata['poster_url']);
        $this->assertEquals('tmdb', $webhook->metadata['source']);
    }

    public function test_refresh_timer_is_configurable(): void
    {
        config(['services.webhook.refresh_timer' => 60]);
        
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertSee('let refreshCountdown = 60;', false);
    }
}