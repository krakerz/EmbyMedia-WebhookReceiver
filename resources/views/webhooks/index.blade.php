@extends('layouts.app')

@section('title', 'Emby Media Dashboard')

@section('content')
<div class="px-4 sm:px-0">
    <!-- Header Section -->
    <div class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">🎬 New Media Releases</h1>
        <p class="text-xl text-gray-600 mb-4">Discover the latest movies, TV shows, and content added to your Emby server</p>
        <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">
            <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
            Live updates every {{ $refreshTimer }} seconds
        </div>
    </div>

    @if($webhooks->count() > 0)
        <!-- Filter Section -->
        <div class="mb-6 flex flex-wrap gap-2 justify-center">
            <button class="filter-btn active px-4 py-2 rounded-full text-sm font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors" data-filter="all">
                All Media
            </button>
            <button class="filter-btn px-4 py-2 rounded-full text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200 transition-colors" data-filter="Movie">
                🎬 Movies
            </button>
            <button class="filter-btn px-4 py-2 rounded-full text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200 transition-colors" data-filter="Episode">
                📺 TV Shows
            </button>
            <button class="filter-btn px-4 py-2 rounded-full text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200 transition-colors" data-filter="Audio">
                🎵 Music
            </button>
        </div>

        <!-- Media Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
            @foreach($webhooks as $webhook)
                <a href="{{ route('webhooks.show', $webhook) }}" 
                   class="media-card block bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden cursor-pointer flex flex-col"
                   data-type="{{ $webhook->item_type }}">
                    
                    <!-- Media Image/Poster -->
                    <div class="relative h-64 bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                        @if(isset($webhook->metadata['poster_url']) || isset($webhook->metadata['backdrop_url']))
                            <img src="{{ $webhook->metadata['poster_url'] ?? $webhook->metadata['backdrop_url'] }}"
                                 alt="{{ $webhook->item_name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <!-- Placeholder for media without images -->
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-50 to-purple-50">
                                <div class="text-center">
                                    @if($webhook->item_type === 'Movie')
                                        <div class="text-6xl mb-2">🎬</div>
                                    @elseif($webhook->item_type === 'Episode')
                                        <div class="text-6xl mb-2">📺</div>
                                    @elseif($webhook->item_type === 'Audio')
                                        <div class="text-6xl mb-2">🎵</div>
                                    @else
                                        <div class="text-6xl mb-2">📁</div>
                                    @endif
                                    <p class="text-sm text-gray-500 font-medium">{{ ucfirst($webhook->item_type ?? 'Media') }}</p>
                                </div>
                            </div>
                        @endif
                        
                        <!-- Media Type Badge -->
                        <div class="absolute top-3 left-3">
                            @if($webhook->isRecentlyAdded())
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-green-500 text-white shadow-lg">
                                    ✨ NEW
                                </span>
                            @endif
                        </div>
                        
                        <!-- Rating Badge -->
                        @if(isset($webhook->metadata['community_rating']) && $webhook->metadata['community_rating'] > 0)
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-yellow-500 text-white shadow-lg">
                                    ⭐ {{ number_format($webhook->metadata['community_rating'], 1) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Media Information -->
                    <div class="p-4 flex flex-col flex-grow">
                        <!-- Title -->
                        <h3 class="font-bold text-lg text-gray-900 mb-2 line-clamp-2 leading-tight">
                            {{ $webhook->item_name ?? 'Unknown Title' }}
                        </h3>
                        
                        <!-- Series Information (for TV shows) -->
                        @if(isset($webhook->metadata['series_name']) && $webhook->metadata['series_name'])
                            <p class="text-sm text-blue-600 font-medium mb-1">
                                {{ $webhook->metadata['series_name'] }}
                                @if(isset($webhook->metadata['season_number']) && isset($webhook->metadata['episode_number']))
                                    - S{{ str_pad($webhook->metadata['season_number'], 2, '0', STR_PAD_LEFT) }}E{{ str_pad($webhook->metadata['episode_number'], 2, '0', STR_PAD_LEFT) }}
                                @endif
                            </p>
                        @endif
                        
                        <!-- Year and Runtime -->
                        <div class="flex items-center text-sm text-gray-500 mb-3 space-x-3">
                            @if(isset($webhook->metadata['year']))
                                <span class="flex items-center">
                                    📅 {{ $webhook->metadata['year'] }}
                                </span>
                            @endif
                            @if(isset($webhook->metadata['runtime']) && $webhook->metadata['runtime'] > 0)
                                <span class="flex items-center">
                                    ⏱️ {{ gmdate('H:i', $webhook->metadata['runtime'] / 10000000) }}
                                </span>
                            @endif
                        </div>
                        
                        <!-- Summary/Overview -->
                        @if(isset($webhook->metadata['overview']) && $webhook->metadata['overview'])
                            <p class="text-sm text-gray-600 mb-3 line-clamp-3 leading-relaxed">
                                {{ $webhook->metadata['overview'] }}
                            </p>
                        @else
                            <p class="text-sm text-gray-400 italic mb-3">
                                No summary available
                            </p>
                        @endif
                        
                        <!-- Genres -->
                        @if(isset($webhook->metadata['genres']) && is_array($webhook->metadata['genres']) && count($webhook->metadata['genres']) > 0)
                            <div class="flex flex-wrap gap-1 mb-3">
                                @foreach(array_slice($webhook->metadata['genres'], 0, 3) as $genre)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-50 text-purple-700">
                                        {{ $genre }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        
                        <!-- Date Added -->
                        <div class="flex items-center justify-between text-xs text-gray-500 border-t pt-3 mt-auto">
                            <span class="flex items-center">
                                🕒 Added {{ $webhook->created_at->diffForHumans() }}
                            </span>
                            <span class="inline-flex items-center text-blue-600 font-medium">
                                View Details →
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-4">
            @if ($webhooks->hasPages())
                <nav aria-label="Page navigation example">
                    <ul class="inline-flex -space-x-px text-sm">
                        {{-- Previous Page Link --}}
                        <li>
                            @if ($webhooks->onFirstPage())
                                <span class="px-3 py-2 ml-0 leading-tight text-gray-400 bg-white border border-gray-300 rounded-l-lg cursor-not-allowed select-none">
                                    <
                                </span>
                            @else
                                <a href="{{ $webhooks->previousPageUrl() }}" class="px-3 py-2 ml-0 leading-tight text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                    <
                                </a>
                            @endif
                        </li>

                        {{-- Pagination Elements --}}
                        @php
                            $start = max(1, $webhooks->currentPage() - 2);
                            $end = min($webhooks->lastPage(), $webhooks->currentPage() + 2);
                            if ($webhooks->currentPage() <= 3) {
                                $end = min(5, $webhooks->lastPage());
                            }
                            if ($webhooks->currentPage() > $webhooks->lastPage() - 2) {
                                $start = max(1, $webhooks->lastPage() - 4);
                            }
                        @endphp

                        @if ($start > 1)
                            <li>
                                <a href="{{ $webhooks->url(1) }}" class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-blue-50 hover:text-blue-700 transition-colors">1</a>
                            </li>
                            @if ($start > 2)
                                <li>
                                    <span class="px-3 py-2 leading-tight text-gray-400 bg-white border border-gray-300 select-none">…</span>
                                </li>
                            @endif
                        @endif

                        @for ($page = $start; $page <= $end; $page++)
                            <li>
                                @if ($page == $webhooks->currentPage())
                                    <span class="px-3 py-2 leading-tight font-bold bg-blue-100 text-blue-700 border border-gray-300 focus:z-10 focus:ring-2 focus:ring-blue-500 transition-colors">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $webhooks->url($page) }}" class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                        {{ $page }}
                                    </a>
                                @endif
                            </li>
                        @endfor

                        @if ($end < $webhooks->lastPage())
                            @if ($end < $webhooks->lastPage() - 1)
                                <li>
                                    <span class="px-3 py-2 leading-tight text-gray-400 bg-white border border-gray-300 select-none">…</span>
                                </li>
                            @endif
                            <li>
                                <a href="{{ $webhooks->url($webhooks->lastPage()) }}" class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-blue-50 hover:text-blue-700 transition-colors">{{ $webhooks->lastPage() }}</a>
                            </li>
                        @endif

                        {{-- Next Page Link --}}
                        <li>
                            @if ($webhooks->hasMorePages())
                                <a href="{{ $webhooks->nextPageUrl() }}" class="px-3 py-2 leading-tight text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                    >
                                </a>
                            @else
                                <span class="px-3 py-2 leading-tight text-gray-400 bg-white border border-gray-300 rounded-r-lg cursor-not-allowed select-none">
                                    >
                                </span>
                            @endif
                        </li>
                    </ul>
                </nav>
            @endif
        </div>
        <style>
            /* Custom highlight for active page to match filter highlight, with normal border */
            .bg-blue-100, .text-blue-700 {
                --tw-bg-opacity: 1;
                background-color: rgb(219 234 254 / var(--tw-bg-opacity)) !important;
                --tw-text-opacity: 1;
                color: rgb(29 78 216 / var(--tw-text-opacity)) !important;
            }
        </style>
    @else
        <!-- Empty State -->
        <div class="text-center py-16">
            <div class="mx-auto h-24 w-24 text-gray-300 mb-6">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-full h-full">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 011 1v1a1 1 0 01-1 1h-1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7H3a1 1 0 01-1-1V5a1 1 0 011-1h4zM9 3v1h6V3H9zm-2 4v12h10V7H7zm3 3a1 1 0 112 0v6a1 1 0 11-2 0v-6zm4 0a1 1 0 112 0v6a1 1 0 11-2 0v-6z" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">No Media Found</h3>
            <p class="text-gray-600 mb-6 max-w-md mx-auto">
                Your Emby server hasn't sent any webhooks yet. Once you add new media to your library, it will appear here automatically.
            </p>
            <div class="bg-blue-50 rounded-lg p-6 max-w-2xl mx-auto">
                <h4 class="font-semibold text-blue-900 mb-2">🔧 Setup Instructions</h4>
                <p class="text-sm text-blue-800 mb-3">
                    Configure your Emby server to send webhooks to this URL:
                </p>
                <code class="bg-blue-100 px-4 py-2 rounded text-sm text-blue-900 font-mono block">
                    {{ url('/emby/webhook') }}
                </code>
                <p class="text-xs text-blue-700 mt-2">
                    Go to Emby Dashboard → Plugins → Webhooks and add this URL
                </p>
            </div>
        </div>
    @endif
</div>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .filter-btn.active {
        background-color: rgb(59 130 246);
        color: white;
    }
</style>

<script>
    // Filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        const filterBtns = document.querySelectorAll('.filter-btn');
        const mediaCards = document.querySelectorAll('.media-card');
        
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Update active button
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filter cards
                mediaCards.forEach(card => {
                    const cardType = card.getAttribute('data-type');
                    if (filter === 'all' || cardType === filter) {
                        card.style.display = 'block';
                        card.style.animation = 'fadeIn 0.3s ease-in';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    });
    
    // Add fade-in animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
</script>
@endsection