<?php

namespace App\Http\Controllers;

use App\Models\EmbyWebhook;
use App\Services\ImageFetchingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class EmbyWebhookController extends Controller
{
    private ImageFetchingService $imageFetchingService;

    public function __construct(ImageFetchingService $imageFetchingService)
    {
        $this->imageFetchingService = $imageFetchingService;
    }

    /**
     * Handle incoming Emby webhook
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $expectedSecret = env('WEBHOOK_SECRET');
        $providedSecret = $request->query('secret');

        if ($expectedSecret && $providedSecret !== $expectedSecret) {
            Log::warning('Unauthorized Emby webhook access attempt', [
                'provided_secret' => $providedSecret,
                'ip_address' => $request->ip()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        try {
            $payload = $request->all();
            
            Log::info('Emby webhook received', ['payload' => $payload]);

            // Extract common data from Emby webhook
            $eventType = $this->extractEventType($payload);
            $itemData = $this->extractItemData($payload);
            $userData = $this->extractUserData($payload);
            $serverData = $this->extractServerData($payload);
            $metadata = $this->extractMetadata($payload);

            // Fetch cover image if this is a new media item
            if (in_array($eventType, ['library.new', 'item.added']) && isset($itemData['type'], $itemData['name'])) {
                $coverImage = $this->imageFetchingService->fetchCoverImage(
                    $metadata,
                    $itemData['type'],
                    $itemData['name'],
                    $payload['Item'] ?? [] // Pass raw item data for Emby image fetching
                );

                if ($coverImage) {
                    $metadata = array_merge($metadata, $coverImage);
                    Log::info('Cover image fetched successfully', [
                        'item_name' => $itemData['name'],
                        'source' => $coverImage['source']
                    ]);
                }

                // For episodes, also try to get series poster if episode image not found
                if ($itemData['type'] === 'Episode' && !isset($coverImage['poster_url'])) {
                    $seriesPoster = $this->imageFetchingService->fetchSeriesPosterForEpisode($metadata);
                    if ($seriesPoster) {
                        $metadata = array_merge($metadata, $seriesPoster);
                        Log::info('Series poster fetched for episode', [
                            'item_name' => $itemData['name'],
                            'series_name' => $metadata['series_name'] ?? 'Unknown'
                        ]);
                    }
                }
            }

            // Store webhook data
            EmbyWebhook::create([
                'event_type' => $eventType,
                'item_type' => $itemData['type'] ?? null,
                'item_name' => $itemData['name'] ?? null,
                'item_path' => $itemData['path'] ?? null,
                'user_name' => $userData['name'] ?? null,
                'server_name' => $serverData['name'] ?? null,
                'metadata' => $metadata,
                'raw_payload' => $payload
            ]);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing Emby webhook', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display webhooks dashboard
     */
    public function index()
    {
        $webhooks = EmbyWebhook::orderBy('created_at', 'desc')->paginate(20);
        $refreshTimer = config('services.webhook.refresh_timer', 30);
        $showRawData = config('services.webhook.show_raw_data', true);
        $showFileLocation = config('services.webhook.show_file_location', true);
        $showEventDetails = config('services.webhook.show_event_details', true);
        return view('webhooks.index', compact('webhooks', 'refreshTimer', 'showRawData', 'showFileLocation', 'showEventDetails'));
    }

    /**
     * Show individual webhook details
     */
    public function show(EmbyWebhook $webhook)
    {
        $showRawData = config('services.webhook.show_raw_data', true);
        $showFileLocation = config('services.webhook.show_file_location', true);
        $showEventDetails = config('services.webhook.show_event_details', true);
        return view('webhooks.show', compact('webhook', 'showRawData', 'showFileLocation', 'showEventDetails'));
    }

    /**
     * Extract event type from payload
     */
    private function extractEventType(array $payload): string
    {
        // Common Emby webhook event types
        if (isset($payload['Event'])) {
            return $payload['Event'];
        }
        
        if (isset($payload['NotificationType'])) {
            return $payload['NotificationType'];
        }

        return 'unknown';
    }

    /**
     * Extract item data from payload
     */
    private function extractItemData(array $payload): array
    {
        $itemData = [];

        if (isset($payload['Item'])) {
            $item = $payload['Item'];
            $itemData['type'] = $item['Type'] ?? null;
            $itemData['name'] = $item['Name'] ?? null;
            $itemData['path'] = $item['Path'] ?? null;
        }

        if (isset($payload['Series'])) {
            $itemData['series'] = $payload['Series']['Name'] ?? null;
        }

        return $itemData;
    }

    /**
     * Extract user data from payload
     */
    private function extractUserData(array $payload): array
    {
        $userData = [];

        if (isset($payload['User'])) {
            $userData['name'] = $payload['User']['Name'] ?? null;
            $userData['id'] = $payload['User']['Id'] ?? null;
        }

        return $userData;
    }

    /**
     * Extract server data from payload
     */
    private function extractServerData(array $payload): array
    {
        $serverData = [];

        if (isset($payload['Server'])) {
            $serverData['name'] = $payload['Server']['Name'] ?? null;
            $serverData['id'] = $payload['Server']['Id'] ?? null;
            $serverData['version'] = $payload['Server']['Version'] ?? null;
        }

        return $serverData;
    }

    /**
     * Extract metadata from payload
     */
    private function extractMetadata(array $payload): array
    {
        $metadata = [];

        if (isset($payload['Item'])) {
            $item = $payload['Item'];
            
            $metadata = [
                'overview' => $item['Overview'] ?? null,
                'year' => $item['ProductionYear'] ?? null,
                'premiere_date' => $item['PremiereDate'] ?? null,
                'end_date' => $item['EndDate'] ?? null,
                'runtime' => $item['RunTimeTicks'] ?? null,
                'genres' => $item['Genres'] ?? [],
                'tags' => $item['Tags'] ?? [],
                'community_rating' => $item['CommunityRating'] ?? null,
                'official_rating' => $item['OfficialRating'] ?? null,
                'date_created' => $item['DateCreated'] ?? null,
                'provider_ids' => $item['ProviderIds'] ?? [],
                'external_urls' => $item['ExternalUrls'] ?? [],
                'media_type' => $item['MediaType'] ?? null,
                'container' => $item['Container'] ?? null,
                'size' => $item['Size'] ?? null,
            ];

            // Add series-specific metadata
            if (isset($payload['Series'])) {
                $metadata['series_name'] = $payload['Series']['Name'] ?? null;
                $metadata['season_number'] = $item['ParentIndexNumber'] ?? null;
                $metadata['episode_number'] = $item['IndexNumber'] ?? null;
            } elseif (isset($item['SeriesName'])) {
                $metadata['series_name'] = $item['SeriesName'];
                $metadata['season_number'] = $item['ParentIndexNumber'] ?? null;
                $metadata['episode_number'] = $item['IndexNumber'] ?? null;
            }
        }

        return array_filter($metadata, function($value) {
            return $value !== null && $value !== '';
        });
    }
}