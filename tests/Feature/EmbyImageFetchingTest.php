<?php

namespace Tests\Feature;

use App\Models\EmbyWebhook;
use App\Services\ImageFetchingService;
use App\Services\EmbyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbyImageFetchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_with_emby_image_data_fetches_cover_image(): void
    {
        $payload = [
            'Event' => 'library.new',
            'Item' => [
                'Type' => 'Episode',
                'Name' => 'Test Episode',
                'Id' => '1623371',
                'SeriesName' => 'Test Series',
                'SeriesId' => '1616083',
                'SeriesPrimaryImageTag' => '899efc5318140f0e5c5186a5038de696',
                'ImageTags' => [
                    'Primary' => 'a336ac61432be8d71f31f500ac1a8004'
                ],
                'ProviderIds' => [
                    'Tvdb' => '11178926',
                    'IMDB' => 'tt37616507'
                ],
                'ProductionYear' => 2025,
                'Overview' => 'Test episode overview'
            ],
            'Server' => [
                'Name' => 'Test Server'
            ]
        ];

        // Set Emby base URL for testing
        config(['services.emby.base_url' => 'http://test-emby-server:8096']);

        $response = $this->postJson('/emby/webhook', $payload);

        $response->assertStatus(200);
        
        $webhook = EmbyWebhook::first();
        $this->assertNotNull($webhook);
        $this->assertArrayHasKey('poster_url', $webhook->metadata);
        $this->assertStringContains('test-emby-server:8096/emby/Items/1623371/Images/Primary', $webhook->metadata['poster_url']);
        $this->assertEquals('emby', $webhook->metadata['source']);
    }

    public function test_raw_webhook_data_display_is_configurable(): void
    {
        $webhook = EmbyWebhook::create([
            'event_type' => 'library.new',
            'item_type' => 'Movie',
            'item_name' => 'Test Movie',
            'metadata' => ['test' => 'data'],
            'raw_payload' => ['test' => 'payload']
        ]);

        // Test with raw data enabled
        config(['services.webhook.show_raw_data' => true]);
        $response = $this->get("/webhook/{$webhook->id}");
        $response->assertStatus(200);
        $response->assertSee('Raw Webhook Data');

        // Test with raw data disabled
        config(['services.webhook.show_raw_data' => false]);
        $response = $this->get("/webhook/{$webhook->id}");
        $response->assertStatus(200);
        $response->assertDontSee('Raw Webhook Data');
    }

    public function test_emby_service_builds_correct_image_url(): void
    {
        config(['services.emby.base_url' => 'http://emby.example.com:8096']);
        
        $embyService = new EmbyService();
        
        $itemData = [
            'Id' => '123456',
            'ImageTags' => [
                'Primary' => 'abc123def456'
            ]
        ];

        $result = $embyService->getCoverImageFromItem($itemData);
        
        $this->assertNotNull($result);
        $this->assertEquals('http://emby.example.com:8096/emby/Items/123456/Images/Primary?tag=abc123def456&quality=90', $result['poster_url']);
        $this->assertEquals('emby', $result['source']);
    }

    public function test_image_fetching_priority_emby_first(): void
    {
        // Mock Emby service to return an image
        config(['services.emby.base_url' => 'http://test-emby:8096']);
        
        $payload = [
            'Event' => 'library.new',
            'Item' => [
                'Type' => 'Movie',
                'Name' => 'Test Movie',
                'Id' => 'test123',
                'ImageTags' => ['Primary' => 'tag123'],
                'ProviderIds' => [
                    'IMDB' => 'tt1234567',
                    'Tvdb' => '987654'
                ]
            ]
        ];

        $response = $this->postJson('/emby/webhook', $payload);
        $response->assertStatus(200);
        
        $webhook = EmbyWebhook::first();
        $this->assertEquals('emby', $webhook->metadata['source']);
        $this->assertStringContains('test-emby:8096', $webhook->metadata['poster_url']);
    }

    public function test_interface_display_options_are_configurable(): void
    {
        $webhook = EmbyWebhook::create([
            'event_type' => 'library.new',
            'item_type' => 'Movie',
            'item_name' => 'Test Movie',
            'item_path' => '/path/to/movie.mkv',
            'metadata' => ['test' => 'data'],
            'raw_payload' => ['test' => 'payload']
        ]);

        // Test with all options enabled
        config([
            'services.webhook.show_raw_data' => true,
            'services.webhook.show_file_location' => true,
            'services.webhook.show_event_details' => true
        ]);
        
        $response = $this->get("/webhook/{$webhook->id}");
        $response->assertStatus(200);
        $response->assertSee('Raw Webhook Data');
        $response->assertSee('File Location');
        $response->assertSee('Webhook Event Details');

        // Test with all options disabled
        config([
            'services.webhook.show_raw_data' => false,
            'services.webhook.show_file_location' => false,
            'services.webhook.show_event_details' => false
        ]);
        
        $response = $this->get("/webhook/{$webhook->id}");
        $response->assertStatus(200);
        $response->assertDontSee('Raw Webhook Data');
        $response->assertDontSee('File Location');
        $response->assertDontSee('Webhook Event Details');
    }

    public function test_external_urls_from_webhook_are_clickable(): void
    {
        $webhook = EmbyWebhook::create([
            'event_type' => 'library.new',
            'item_type' => 'Episode',
            'item_name' => 'Test Episode',
            'metadata' => [
                'external_urls' => [
                    [
                        'Name' => 'IMDb',
                        'Url' => 'https://www.imdb.com/title/tt37616507'
                    ],
                    [
                        'Name' => 'TheTVDB',
                        'Url' => 'https://thetvdb.com/?tab=episode&id=11178926'
                    ],
                    [
                        'Name' => 'Trakt',
                        'Url' => 'https://trakt.tv/search/imdb/tt37616507'
                    ]
                ],
                'provider_ids' => [
                    'IMDB' => 'tt37616507',
                    'Tvdb' => '11178926'
                ]
            ],
            'raw_payload' => ['test' => 'payload']
        ]);

        $response = $this->get("/webhook/{$webhook->id}");
        $response->assertStatus(200);
        
        // Check for external URLs section
        $response->assertSee('External Links');
        
        // Check for clickable external URLs from webhook response
        $response->assertSee('https://www.imdb.com/title/tt37616507');
        $response->assertSee('https://thetvdb.com/?tab=episode&id=11178926');
        $response->assertSee('https://trakt.tv/search/imdb/tt37616507');
        
        // Check for provider IDs section (non-clickable)
        $response->assertSee('Provider IDs');
        $response->assertSee('tt37616507');
        $response->assertSee('11178926');
        
        // Check that external links have target="_blank"
        $response->assertSee('target="_blank"');
        
        // Check that external links have proper labels
        $response->assertSee('View on IMDb');
        $response->assertSee('View on TheTVDB');
        $response->assertSee('View on Trakt');
    }