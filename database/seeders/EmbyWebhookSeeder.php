<?php

namespace Database\Seeders;

use App\Models\EmbyWebhook;
use Illuminate\Database\Seeder;

class EmbyWebhookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sampleWebhooks = [
            [
                'event_type' => 'library.new',
                'item_type' => 'Movie',
                'item_name' => 'The Matrix',
                'item_path' => '/media/movies/The Matrix (1999)/The Matrix.mkv',
                'user_name' => 'admin',
                'server_name' => 'Home Media Server',
                'metadata' => [
                    'year' => 1999,
                    'overview' => 'A computer programmer is led to fight an underground war against powerful computers who have constructed his entire reality with a system called the Matrix.',
                    'genres' => ['Action', 'Sci-Fi'],
                    'community_rating' => 8.7,
                    'official_rating' => 'R',
                    'runtime' => 1360000000000,
                    'container' => 'mkv',
                    'size' => 2147483648,
                ],
                'raw_payload' => [
                    'Event' => 'library.new',
                    'Item' => [
                        'Name' => 'The Matrix',
                        'Type' => 'Movie',
                        'ProductionYear' => 1999,
                        'Path' => '/media/movies/The Matrix (1999)/The Matrix.mkv'
                    ]
                ],
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'event_type' => 'library.new',
                'item_type' => 'Episode',
                'item_name' => 'Pilot',
                'item_path' => '/media/tv/Breaking Bad/Season 01/S01E01 - Pilot.mkv',
                'user_name' => 'admin',
                'server_name' => 'Home Media Server',
                'metadata' => [
                    'year' => 2008,
                    'overview' => 'Walter White, a struggling high school chemistry teacher, is diagnosed with inoperable lung cancer.',
                    'genres' => ['Crime', 'Drama', 'Thriller'],
                    'community_rating' => 9.0,
                    'official_rating' => 'TV-MA',
                    'runtime' => 2880000000000,
                    'series_name' => 'Breaking Bad',
                    'season_number' => 1,
                    'episode_number' => 1,
                    'container' => 'mkv',
                ],
                'raw_payload' => [
                    'Event' => 'library.new',
                    'Item' => [
                        'Name' => 'Pilot',
                        'Type' => 'Episode',
                        'ProductionYear' => 2008,
                        'Path' => '/media/tv/Breaking Bad/Season 01/S01E01 - Pilot.mkv',
                        'ParentIndexNumber' => 1,
                        'IndexNumber' => 1
                    ],
                    'Series' => [
                        'Name' => 'Breaking Bad'
                    ]
                ],
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour(),
            ],
            [
                'event_type' => 'playback.start',
                'item_type' => 'Movie',
                'item_name' => 'Inception',
                'item_path' => '/media/movies/Inception (2010)/Inception.mkv',
                'user_name' => 'john_doe',
                'server_name' => 'Home Media Server',
                'metadata' => [
                    'year' => 2010,
                    'overview' => 'A thief who steals corporate secrets through the use of dream-sharing technology.',
                    'genres' => ['Action', 'Sci-Fi', 'Thriller'],
                    'community_rating' => 8.8,
                    'official_rating' => 'PG-13',
                    'runtime' => 8880000000000,
                    'container' => 'mkv',
                ],
                'raw_payload' => [
                    'Event' => 'playback.start',
                    'Item' => [
                        'Name' => 'Inception',
                        'Type' => 'Movie',
                        'ProductionYear' => 2010,
                        'Path' => '/media/movies/Inception (2010)/Inception.mkv'
                    ],
                    'User' => [
                        'Name' => 'john_doe'
                    ]
                ],
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'event_type' => 'library.new',
                'item_type' => 'Album',
                'item_name' => 'Dark Side of the Moon',
                'item_path' => '/media/music/Pink Floyd/Dark Side of the Moon',
                'user_name' => 'admin',
                'server_name' => 'Home Media Server',
                'metadata' => [
                    'year' => 1973,
                    'genres' => ['Progressive Rock', 'Psychedelic Rock'],
                    'community_rating' => 9.5,
                    'media_type' => 'Audio',
                ],
                'raw_payload' => [
                    'Event' => 'library.new',
                    'Item' => [
                        'Name' => 'Dark Side of the Moon',
                        'Type' => 'MusicAlbum',
                        'ProductionYear' => 1973,
                        'Path' => '/media/music/Pink Floyd/Dark Side of the Moon'
                    ]
                ],
                'created_at' => now()->subMinutes(15),
                'updated_at' => now()->subMinutes(15),
            ]
        ];

        foreach ($sampleWebhooks as $webhook) {
            EmbyWebhook::create($webhook);
        }
    }
}