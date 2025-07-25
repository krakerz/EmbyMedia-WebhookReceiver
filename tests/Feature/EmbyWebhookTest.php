<?php

namespace Tests\Feature;

use App\Models\EmbyWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmbyWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_endpoint_accepts_emby_data(): void
    {
        $payload = [
            'Event' => 'library.new',
            'Item' => [
                'Name' => 'Test Movie',
                'Type' => 'Movie',
                'Path' => '/media/movies/test-movie.mkv',
                'ProductionYear' => 2023,
                'Overview' => 'A test movie for webhook testing',
                'Genres' => ['Action', 'Adventure'],
                'CommunityRating' => 8.5,
                'RunTimeTicks' => 72000000000,
            ],
            'User' => [
                'Name' => 'TestUser',
                'Id' => 'test-user-id'
            ],
            'Server' => [
                'Name' => 'Test Emby Server',
                'Version' => '4.7.0.0'
            ]
        ];

        $response = $this->postJson('/emby/webhook', $payload);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('emby_webhooks', [
            'event_type' => 'library.new',
            'item_name' => 'Test Movie',
            'item_type' => 'Movie',
            'user_name' => 'TestUser',
            'server_name' => 'Test Emby Server'
        ]);

        $webhook = EmbyWebhook::first();
        $this->assertNotNull($webhook->metadata);
        $this->assertEquals(2023, $webhook->metadata['year']);
        $this->assertEquals(['Action', 'Adventure'], $webhook->metadata['genres']);
        $this->assertEquals(8.5, $webhook->metadata['community_rating']);
    }

    public function test_dashboard_displays_webhooks(): void
    {
        EmbyWebhook::create([
            'event_type' => 'library.new',
            'item_name' => 'Test Movie',
            'item_type' => 'Movie',
            'user_name' => 'TestUser',
            'server_name' => 'Test Server',
            'metadata' => ['year' => 2023],
            'raw_payload' => ['test' => 'data']
        ]);

        $response = $this->get('/');

        $response->assertStatus(200)
                 ->assertSee('Test Movie')
                 ->assertSee('library.new')
                 ->assertSee('TestUser');
    }

    public function test_webhook_detail_page(): void
    {
        $webhook = EmbyWebhook::create([
            'event_type' => 'library.new',
            'item_name' => 'Test Movie',
            'item_type' => 'Movie',
            'user_name' => 'TestUser',
            'server_name' => 'Test Server',
            'metadata' => [
                'year' => 2023,
                'genres' => ['Action', 'Adventure'],
                'overview' => 'A test movie'
            ],
            'raw_payload' => ['test' => 'data']
        ]);

        $response = $this->get("/webhook/{$webhook->id}");

        $response->assertStatus(200)
                 ->assertSee('Test Movie')
                 ->assertSee('2023')
                 ->assertSee('Action')
                 ->assertSee('Adventure');
    }

    public function test_webhook_handles_invalid_data(): void
    {
        $response = $this->postJson('/emby/webhook', []);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('emby_webhooks', [
            'event_type' => 'unknown'
        ]);
    }
}