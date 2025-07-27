@extends('layouts.app')

@section('title', ($webhook->item_name ?? 'Unknown') . ' - Media Details')

@section('content')
<div class="px-4 sm:px-0">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
                <li>
                    <a href="{{ route('webhooks.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center">
                        <svg class="flex-shrink-0 h-5 w-5 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        Back to Media Library
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 h-5 w-5 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                        </svg>
                        <span class="ml-4 text-sm font-medium text-gray-500">Media Details</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Media Header -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
        <div class="md:flex">
            <!-- Media Poster/Image -->
            <div class="md:w-1/3 lg:w-1/4">
                <div class="h-96 md:h-full bg-gradient-to-br from-gray-100 to-gray-200">
                    @if(isset($webhook->metadata['poster_url']) || isset($webhook->metadata['backdrop_url']))
                        <img src="{{ $webhook->metadata['poster_url'] ?? $webhook->metadata['backdrop_url'] }}" 
                             alt="{{ $webhook->item_name }}" 
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-50 to-purple-50">
                            <div class="text-center">
                                @if($webhook->item_type === 'Movie')
                                    <div class="text-8xl mb-4">🎬</div>
                                @elseif($webhook->item_type === 'Episode')
                                    <div class="text-8xl mb-4">📺</div>
                                @elseif($webhook->item_type === 'Audio')
                                    <div class="text-8xl mb-4">🎵</div>
                                @else
                                    <div class="text-8xl mb-4">📁</div>
                                @endif
                                <p class="text-lg text-gray-500 font-medium">{{ ucfirst($webhook->item_type ?? 'Media') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Media Information -->
            <div class="md:w-2/3 lg:w-3/4 p-6 md:p-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 space-y-2 sm:space-y-0">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">
                            {{ $webhook->item_name ?? 'Unknown Title' }}
                        </h1>
                        
                        <!-- Series Information -->
                        @if(isset($webhook->metadata['series_name']) && $webhook->metadata['series_name'])
                            <p class="text-xl text-blue-600 font-semibold mb-2">
                                {{ $webhook->metadata['series_name'] }}
                                @if(isset($webhook->metadata['season_number']) && isset($webhook->metadata['episode_number']))
                                    <span class="text-gray-500">
                                        - Season {{ $webhook->metadata['season_number'] }}, Episode {{ $webhook->metadata['episode_number'] }}
                                    </span>
                                @endif
                            </p>
                        @endif
                    </div>
                    
                    <!-- Status Badge -->
                    @if($webhook->isRecentlyAdded())
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-green-500 text-white shadow-lg">
                            ✨ Recently Added
                        </span>
                    @endif
                </div>
                
                <!-- Quick Info Row -->
                <div class="flex flex-wrap items-center gap-4 mb-6 text-sm text-gray-600">
                    @if(isset($webhook->metadata['year']))
                        <span class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                            📅 {{ $webhook->metadata['year'] }}
                        </span>
                    @endif
                    
                    @if(isset($webhook->metadata['runtime']) && $webhook->metadata['runtime'] > 0)
                        <span class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                            ⏱️ {{ gmdate('H:i', $webhook->metadata['runtime'] / 10000000) }}
                        </span>
                    @endif
                    
                    @if(isset($webhook->metadata['official_rating']) && $webhook->metadata['official_rating'])
                        <span class="flex items-center bg-gray-100 px-3 py-1 rounded-full">
                            🏷️ {{ $webhook->metadata['official_rating'] }}
                        </span>
                    @endif
                    
                    @if(isset($webhook->metadata['community_rating']) && $webhook->metadata['community_rating'] > 0)
                        <span class="flex items-center bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full font-medium">
                            ⭐ {{ number_format($webhook->metadata['community_rating'], 1) }}/10
                        </span>
                    @endif
                </div>
                
                <!-- Overview/Summary -->
                @if(isset($webhook->metadata['overview']) && $webhook->metadata['overview'])
                    <div class="mb-6">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-lg font-semibold text-gray-900">📖 Summary</h3>
                            @if(isset($webhook->metadata['premiere_date']) && $webhook->metadata['premiere_date'])
                                <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                                    🎬 Premiered {{ \Carbon\Carbon::parse($webhook->metadata['premiere_date'])->format('M d, Y') }}
                                </span>
                            @endif
                        </div>
                        <p class="text-gray-700 leading-relaxed">
                            {{ $webhook->metadata['overview'] }}
                        </p>
                    </div>
                @endif
                
                <!-- Genres -->
                @if(isset($webhook->metadata['genres']) && is_array($webhook->metadata['genres']) && count($webhook->metadata['genres']) > 0)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">🎭 Genres</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($webhook->metadata['genres'] as $genre)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                    {{ $genre }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <!-- Tags -->
                @if(isset($webhook->metadata['tags']) && is_array($webhook->metadata['tags']) && count($webhook->metadata['tags']) > 0)
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">🏷️ Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($webhook->metadata['tags'] as $tag)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <!-- Date Added -->
                <div class="text-sm text-gray-500 border-t pt-4">
                    <span class="flex items-center">
                        🕒 Added to library {{ $webhook->created_at->format('F j, Y \a\t g:i A') }}
                        <span class="ml-2 text-gray-400">({{ $webhook->created_at->diffForHumans() }})</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Technical Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Media Information -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-semibold text-gray-900">📊 Media Information</h3>
            </div>
            <div class="p-6">
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Media Type</dt>
                        <dd class="text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ ucfirst($webhook->item_type ?? 'Unknown') }}
                            </span>
                        </dd>
                    </div>
                    
                    @if(config('app.debug') && $webhook->server_name)
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Server</dt>
                            <dd class="text-sm text-gray-900">{{ $webhook->server_name }}</dd>
                        </div>
                    @endif
                    
                    @if($webhook->user_name)
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Added by</dt>
                            <dd class="text-sm text-gray-900">{{ $webhook->user_name }}</dd>
                        </div>
                    @endif
                    
                    @if(isset($webhook->metadata['container']))
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Container</dt>
                            <dd class="text-sm text-gray-900">{{ strtoupper($webhook->metadata['container']) }}</dd>
                        </div>
                    @endif
                    
                    @if(isset($webhook->metadata['width']) && $webhook->metadata['width'] > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Width</dt>
                            <dd class="text-sm text-gray-900">{{ $webhook->metadata['width'] }}px</dd>
                        </div>
                    @endif
                    
                    @if(isset($webhook->metadata['height']) && $webhook->metadata['height'] > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Height</dt>
                            <dd class="text-sm text-gray-900">{{ $webhook->metadata['height'] }}px</dd>
                        </div>
                    @endif
                    
                    @if(isset($webhook->metadata['size']) && $webhook->metadata['size'] > 0)
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">File Size</dt>
                            <dd class="text-sm text-gray-900">
                                @if($webhook->metadata['size'] > 1073741824)
                                    {{ number_format($webhook->metadata['size'] / 1073741824, 2) }} GB
                                @else
                                    {{ number_format($webhook->metadata['size'] / 1048576, 2) }} MB
                                @endif
                            </dd>
                        </div>
                    @endif
                    
                    @if(isset($webhook->metadata['date_created']))
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Date Created</dt>
                            <dd class="text-sm text-gray-900">
                                {{ \Carbon\Carbon::parse($webhook->metadata['date_created'])->format('M d, Y') }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
        
        <!-- External IDs -->
        @if(isset($webhook->metadata['external_urls']) && is_array($webhook->metadata['external_urls']) && count($webhook->metadata['external_urls']) > 0)
            <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">🔗 External Links</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        @foreach($webhook->metadata['external_urls'] as $externalUrl)
                            @if(isset($externalUrl['Name']) && isset($externalUrl['Url']))
                                <div class="flex justify-between items-center">
                                    <dt class="text-sm font-medium text-gray-500">{{ $externalUrl['Name'] }}</dt>
                                    <dd class="text-sm">
                                        <a href="{{ $externalUrl['Url'] }}" target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center">
                                            View on {{ $externalUrl['Name'] }}
                                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    </dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                </div>
            </div>
        @endif

        @if(config('app.show_provider_ids', true) && isset($webhook->metadata['provider_ids']) && is_array($webhook->metadata['provider_ids']) && count($webhook->metadata['provider_ids']) > 0)
            <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">🏷️ Provider IDs</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        @foreach($webhook->metadata['provider_ids'] as $provider => $id)
                            @if($id)
                                <div class="flex justify-between items-center">
                                    <dt class="text-sm font-medium text-gray-500">{{ ucfirst($provider) }}</dt>
                                    <dd class="text-sm">
                                        <span class="text-gray-900 font-mono">{{ $id }}</span>
                                    </dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                </div>
            </div>
        @endif
    </div>

    <!-- File Path -->
    @if($webhook->item_path && ($showFileLocation ?? true))
        <div class="bg-white shadow-lg rounded-xl overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-semibold text-gray-900">📁 File Location</h3>
            </div>
            <div class="p-6">
                <code class="block bg-gray-100 p-4 rounded-lg text-sm font-mono text-gray-800 break-all">
                    {{ $webhook->item_path }}
                </code>
            </div>
        </div>
    @endif

    @if($showEventDetails ?? true)
        <!-- Event Details -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-semibold text-gray-900">📡 Webhook Event Details</h3>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Event Type</dt>
                        <dd>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                {{ $webhook->event_type }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Received</dt>
                        <dd class="text-sm text-gray-900">
                            {{ $webhook->created_at->format('F j, Y \a\t g:i:s A') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    @endif

    @if($showRawData ?? true)
        <!-- Raw Data (Collapsible) -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <button onclick="toggleRawData()" class="w-full text-left flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">🔧 Raw Webhook Data</h3>
                    <svg id="raw-data-icon" class="h-5 w-5 text-gray-500 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
            <div id="raw-data-content" class="hidden p-6">
                <p class="text-sm text-gray-600 mb-4">Complete webhook payload received from Emby server</p>
                <pre class="bg-gray-50 rounded-lg p-4 text-xs overflow-x-auto max-h-96 overflow-y-auto"><code>{{ json_encode($webhook->raw_payload, JSON_PRETTY_PRINT) }}</code></pre>
            </div>
        </div>
    @endif
</div>

<script>
    function toggleRawData() {
        const content = document.getElementById('raw-data-content');
        const icon = document.getElementById('raw-data-icon');
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }
</script>
@endsection