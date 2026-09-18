@extends('layouts.app')

@section('title', 'Emby Media Dashboard')

@section('content')
<div class="px-4 sm:px-0">
    <!-- Header Section -->
    <div class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center justify-center gap-3">
            <x-icon name="movie" class="w-9 h-9 text-blue-600 dark:text-blue-400" />
            New Media Releases
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 mb-4">Discover the latest movies, TV shows, and content added to your Emby server</p>
        <div class="inline-flex items-center px-4 py-2 bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 rounded-full text-sm font-medium">
            <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
            Live updates every
            @if($refreshTimer < 60)
                {{ $refreshTimer }} seconds
            @elseif($refreshTimer % 3600 === 0)
                {{ $refreshTimer / 3600 }} hour{{ $refreshTimer / 3600 > 1 ? 's' : '' }}
            @elseif($refreshTimer % 60 === 0)
                {{ $refreshTimer / 60 }} minute{{ $refreshTimer / 60 > 1 ? 's' : '' }}
            @else
                {{ $refreshTimer }} seconds {{-- Fallback for values not exact minutes/hours --}}
            @endif
        </div>
    </div>

    @if($webhooks->count() > 0)
        <!-- Toggle Controls -->
        <div class="mb-4 flex flex-wrap gap-4 justify-center">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" id="toggle-images" class="form-checkbox h-4 w-4 text-blue-600 rounded" checked>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                    <x-icon name="image" class="w-4 h-4" /> Show Images
                </span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" id="toggle-descriptions" class="form-checkbox h-4 w-4 text-blue-600 rounded" checked>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                    <x-icon name="description" class="w-4 h-4" /> Show Overview
                </span>
            </label>
        </div>

        <!-- Filter Section -->
        <div class="mb-6 flex flex-wrap gap-2 justify-center">
            <a href="{{ route('webhooks.index') }}"
               class="filter-btn {{ !$filter || $filter === 'all' ? 'active' : '' }} px-4 py-2 rounded-full text-sm font-medium {{ !$filter || $filter === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' }} hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                All Media
            </a>
            @foreach($allowedItemTypes as $itemType)
                <a href="{{ route('webhooks.index', ['filter' => $itemType]) }}"
                   class="filter-btn {{ $filter === $itemType ? 'active' : '' }} px-4 py-2 rounded-full text-sm font-medium {{ $filter === $itemType ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' }} hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                    @if($itemType === 'Movie')
                        <span class="inline-flex items-center gap-1.5"><x-icon name="movie" class="w-4 h-4" /> Movies</span>
                    @elseif($itemType === 'Episode')
                        <span class="inline-flex items-center gap-1.5"><x-icon name="tv" class="w-4 h-4" /> TV Shows</span>
                    @elseif($itemType === 'Audio')
                        <span class="inline-flex items-center gap-1.5"><x-icon name="music_note" class="w-4 h-4" /> Music</span>
                    @else
                        {{ $itemType }}
                    @endif
                </a>
            @endforeach
        </div>

        <!-- Media Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
            @foreach($webhooks as $webhook)
                <a href="{{ route('webhooks.show', $webhook) }}"
                   class="media-card block bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl dark:shadow-black/30 transition-all duration-300 transform hover:-translate-y-1 overflow-hidden cursor-pointer flex flex-col">


                    <!-- Media Image/Poster -->
                    <div class="media-image relative h-64 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
                        @if(isset($webhook->metadata['poster_url']) || isset($webhook->metadata['backdrop_url']))
                            <img src="{{ $webhook->metadata['poster_url'] ?? $webhook->metadata['backdrop_url'] }}"
                                 alt="{{ $webhook->item_name }}"
                                 onerror="handleMediaImageError(this, '{{ $webhook->uuid }}', '{{ $webhook->item_type }}', 'text-6xl')"
                                 class="w-full h-full object-cover media-image-content">
                        @else
                            <!-- Placeholder for media without images -->
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-800 media-image-content">
                                <div class="text-center">
                                    @if($webhook->item_type === 'Movie')
                                        <x-icon name="movie" class="w-16 h-16 mx-auto mb-2 text-gray-400 dark:text-gray-500" />
                                    @elseif($webhook->item_type === 'Episode')
                                        <x-icon name="tv" class="w-16 h-16 mx-auto mb-2 text-gray-400 dark:text-gray-500" />
                                    @elseif($webhook->item_type === 'Audio')
                                        <x-icon name="music_note" class="w-16 h-16 mx-auto mb-2 text-gray-400 dark:text-gray-500" />
                                    @else
                                        <x-icon name="folder" class="w-16 h-16 mx-auto mb-2 text-gray-400 dark:text-gray-500" />
                                    @endif
                                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ ucfirst($webhook->item_type ?? 'Media') }}</p>
                                </div>
                            </div>
                        @endif
                        
                        <!-- Media Type Badge -->
                        <div class="absolute top-3 left-3">
                            @if($webhook->isRecentlyAdded())
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-green-500 text-white shadow-lg">
                                    <x-icon name="auto_awesome" class="w-3.5 h-3.5" /> NEW
                                </span>
                            @endif
                        </div>
                        
                        <!-- Rating Badge -->
                        @if(isset($webhook->metadata['community_rating']) && $webhook->metadata['community_rating'] > 0)
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-yellow-500 text-white shadow-lg">
                                    <x-icon name="star" class="w-3.5 h-3.5" /> {{ number_format($webhook->metadata['community_rating'], 1) }}
                                </span>
                            </div>
                        @endif

                        <!-- Year and Runtime -->
                        @if(isset($webhook->metadata['year']))
                            <div class="absolute bottom-3 left-3">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 shadow-lg">
                                    <x-icon name="calendar_today" class="w-3.5 h-3.5" /> {{ $webhook->metadata['year'] }}
                                </span>
                            </div>
                        @endif
                        @if(isset($webhook->metadata['runtime']) && $webhook->metadata['runtime'] > 0)
                            <div class="absolute bottom-3 right-3">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 shadow-lg">
                                    <x-icon name="timer" class="w-3.5 h-3.5" /> {{ gmdate('H:i', $webhook->metadata['runtime'] / 10000000) }}
                                </span>
                            </div>
                        @endif

                        <!-- Season/Episode Information (for TV shows) -->
                        @if(isset($webhook->metadata['season_number']) && isset($webhook->metadata['episode_number']))
                            <div class="absolute bottom-10 left-3"> {{-- This new tag will be at bottom-3 --}}
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 shadow-lg">
                                    S{{ str_pad($webhook->metadata['season_number'], 2, '0', STR_PAD_LEFT) }} E{{ str_pad($webhook->metadata['episode_number'], 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Media Information -->
                    <div class="p-4 flex flex-col flex-grow">
                        <!-- Title -->
                        <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 mb-2 line-clamp-1 leading-tight">
                            {{ $webhook->item_name ?? 'Unknown Title' }}
                        </h3>

                        <!-- Series Name (for TV shows) -->
                        @if(isset($webhook->metadata['series_name']) && $webhook->metadata['series_name'])
                            <p class="text-sm text-blue-600 dark:text-blue-400 font-medium mb-1 line-clamp-1">
                                {{ $webhook->metadata['series_name'] }}
                            </p>
                        @endif

                        <!-- Summary/Overview -->
                        <div class="media-description">
                            @if(isset($webhook->metadata['overview']) && $webhook->metadata['overview'])
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-4 leading-relaxed">
                                    {{ $webhook->metadata['overview'] }}
                                </p>
                            @else
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic mb-3">
                                    No summary available
                                </p>
                            @endif
                        </div>

                        <!-- Genres -->
                        @if(isset($webhook->metadata['genres']) && is_array($webhook->metadata['genres']) && count($webhook->metadata['genres']) > 0)
                            <div class="flex flex-wrap gap-1 mb-3">
                                @foreach(array_slice($webhook->metadata['genres'], 0, 3) as $genre)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-50 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">
                                        {{ $genre }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <!-- Date Added -->
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 border-t dark:border-gray-700 pt-3 mt-auto">
                            <span class="flex items-center gap-1">
                                <x-icon name="schedule" class="w-3.5 h-3.5" /> Added {{ $webhook->created_at->diffForHumans() }}
                            </span>
                            <span class="inline-flex items-center text-blue-600 dark:text-blue-400 font-medium">
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
                                <span class="px-3 py-2 ml-0 leading-tight text-gray-400 dark:text-gray-600 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-l-lg cursor-not-allowed select-none">
                                    <
                                </span>
                            @else
                                <a href="{{ $webhooks->previousPageUrl() }}" class="px-3 py-2 ml-0 leading-tight text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-l-lg hover:bg-blue-50 dark:hover:bg-blue-900/40 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
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
                                <a href="{{ $webhooks->url(1) }}" class="px-3 py-2 leading-tight text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-blue-900/40 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">1</a>
                            </li>
                            @if ($start > 2)
                                <li>
                                    <span class="px-3 py-2 leading-tight text-gray-400 dark:text-gray-600 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 select-none">…</span>
                                </li>
                            @endif
                        @endif

                        @for ($page = $start; $page <= $end; $page++)
                            <li>
                                @if ($page == $webhooks->currentPage())
                                    <span class="px-3 py-2 leading-tight font-bold bg-blue-100 text-blue-700 border border-gray-300 dark:border-gray-700 focus:z-10 focus:ring-2 focus:ring-blue-500 transition-colors">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $webhooks->url($page) }}" class="px-3 py-2 leading-tight text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-blue-900/40 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                                        {{ $page }}
                                    </a>
                                @endif
                            </li>
                        @endfor

                        @if ($end < $webhooks->lastPage())
                            @if ($end < $webhooks->lastPage() - 1)
                                <li>
                                    <span class="px-3 py-2 leading-tight text-gray-400 dark:text-gray-600 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 select-none">…</span>
                                </li>
                            @endif
                            <li>
                                <a href="{{ $webhooks->url($webhooks->lastPage()) }}" class="px-3 py-2 leading-tight text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-blue-900/40 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">{{ $webhooks->lastPage() }}</a>
                            </li>
                        @endif

                        {{-- Next Page Link --}}
                        <li>
                            @if ($webhooks->hasMorePages())
                                <a href="{{ $webhooks->nextPageUrl() }}" class="px-3 py-2 leading-tight text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-r-lg hover:bg-blue-50 dark:hover:bg-blue-900/40 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                                    >
                                </a>
                            @else
                                <span class="px-3 py-2 leading-tight text-gray-400 dark:text-gray-600 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-r-lg cursor-not-allowed select-none">
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

            .dark .bg-blue-100, .dark .text-blue-700 {
                background-color: rgb(30 58 138 / 0.4) !important;
                color: rgb(147 197 253) !important;
            }
        </style>
    @else
        <!-- Empty State -->
        <div class="text-center py-16">
            <div class="mx-auto h-24 w-24 text-gray-300 dark:text-gray-700 mb-6">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-full h-full">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 011 1v1a1 1 0 01-1 1h-1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7H3a1 1 0 01-1-1V5a1 1 0 011-1h4zM9 3v1h6V3H9zm-2 4v12h10V7H7zm3 3a1 1 0 112 0v6a1 1 0 11-2 0v-6zm4 0a1 1 0 112 0v6a1 1 0 11-2 0v-6z" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">No Media Found</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                Your Emby server hasn't sent any webhooks yet. Once you add new media to your library, it will appear here automatically.
            </p>
            <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-6 max-w-2xl mx-auto">
                <h4 class="font-semibold text-blue-900 dark:text-blue-300 mb-2 flex items-center justify-center gap-1.5"><x-icon name="tune" class="w-4 h-4" /> Setup Instructions</h4>
                <p class="text-sm text-blue-800 dark:text-blue-200 mb-3">
                    Configure your Emby server to send webhooks to this URL:
                </p>
                <code class="bg-blue-100 dark:bg-blue-900/50 px-4 py-2 rounded text-sm text-blue-900 dark:text-blue-200 font-mono block">
                    {{ url('/emby/webhook') }}
                </code>
                <p class="text-xs text-blue-700 dark:text-blue-300 mt-2">
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
    
    .media-image.hidden {
        display: none;
    }
    
    .media-description.hidden {
        display: none;
    }
    
    .media-image-content.blurred {
        filter: blur(15px);
    }
</style>

<script>
    // Cookie management functions
    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/';
    }
    
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for(let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    // Toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const toggleImages = document.getElementById('toggle-images');
        const toggleDescriptions = document.getElementById('toggle-descriptions');
        const mediaImages = document.querySelectorAll('.media-image-content');
        const mediaDescriptions = document.querySelectorAll('.media-description');
        
        // Load saved preferences from cookies (default: enabled)
        const showImages = getCookie('show_images') !== null ? getCookie('show_images') === 'true' : false;
        const showDescriptions = getCookie('show_descriptions') !== null ? getCookie('show_descriptions') === 'true' : false;
        
        // Set initial states
        toggleImages.checked = showImages;
        toggleDescriptions.checked = showDescriptions;
        
        // Apply initial visibility
        updateImageVisibility(showImages);
        updateDescriptionVisibility(showDescriptions);
        
        // Add event listeners
        toggleImages.addEventListener('change', function() {
            const isChecked = this.checked;
            setCookie('show_images', isChecked, 30); // Save for 30 days
            updateImageVisibility(isChecked);
        });
        
        toggleDescriptions.addEventListener('change', function() {
            const isChecked = this.checked;
            setCookie('show_descriptions', isChecked, 30); // Save for 30 days
            updateDescriptionVisibility(isChecked);
        });
        
        function updateImageVisibility(show) {
            mediaImages.forEach(image => {
                if (show) {
                    image.classList.remove('hidden', 'blurred');
                } else {
                    image.classList.add('blurred');
                }
            });
        }
        
        function updateDescriptionVisibility(show) {
            mediaDescriptions.forEach(description => {
                if (show) {
                    description.classList.remove('hidden');
                } else {
                    description.classList.add('hidden');
                }
            });
        }
        
        console.log('Emby Media Dashboard loaded with toggle controls');
    });
</script>
@endsection