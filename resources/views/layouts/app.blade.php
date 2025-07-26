<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Emby Media Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="https://cdn.jsdelivr.net/gh/homarr-labs/dashboard-icons/png/emby.png" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .media-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .media-card:hover {
            transform: translateY(-8px) scale(1.02);
        }
        
        .pulse-dot {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white/80 backdrop-blur-md shadow-sm border-b border-white/20 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center">
                            <img src="/images/emby.svg" alt="Emby Logo" class="w-6 h-6" />
                        </div>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">
                            <a href="{{ route('webhooks.index') }}" class="hover:text-emby-600 transition-colors">
                                Emby Media Dashboard
                            </a>
                        </h1>
                        <p class="text-xs text-gray-500 hidden sm:block">Your personal media library</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center space-x-2 text-sm text-gray-600">
                        <div class="w-2 h-2 bg-green-400 rounded-full pulse-dot"></div>
                        <span>Live monitoring</span>
                    </div>
                    
                    <!-- Stats Badge -->
                    <div class="hidden sm:flex items-center space-x-2 bg-emby-50 px-3 py-1 rounded-full">
                        <span class="text-xs font-medium text-emby-700">
                            {{ \App\Models\EmbyWebhook::count() }} items tracked
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 fade-in">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="mt-16 bg-white/50 backdrop-blur-sm border-t border-gray-200">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <div class="w-6 h-6 rounded-lg flex items-center justify-center">
                        <img src="/images/emby.svg" alt="Emby Logo" class="w-4 h-4" />
                    </div>
                    <span class="text-sm font-medium text-gray-700">Emby Webhook Dashboard</span>
                </div>
                
                <div class="flex items-center space-x-6 text-sm text-gray-500">
                    <span>Built with ❤️ for media enthusiasts</span>
                    <span>•</span>
                    <span>Auto-refresh: 30s</span>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-200 text-center">
                @if(config('app.debug'))
                <p class="text-xs text-gray-400">
                    Webhook endpoint: <code class="bg-gray-100 px-2 py-1 rounded text-gray-600">{{ url('/emby/webhook') }}</code>
                </p>
                @endif
            </div>
        </div>
    </footer>

    <!-- Auto-refresh Script -->
    <script>
        // Auto-refresh functionality with visual indicator
        let refreshTimer;
        let refreshCountdown = {{ $refreshTimer ?? 30 }};
        
        function startRefreshTimer() {
            refreshTimer = setInterval(() => {
                refreshCountdown--;
                
                // Update any countdown displays
                const countdownElements = document.querySelectorAll('.refresh-countdown');
                countdownElements.forEach(el => {
                    el.textContent = refreshCountdown;
                });
                
                if (refreshCountdown <= 0) {
                    // Add a subtle loading indicator
                    document.body.style.opacity = '0.9';
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
            }, 1000);
        }
        
        // Start the timer when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Only auto-refresh on the main index page
            if (window.location.pathname === '/' || window.location.pathname.includes('webhooks')) {
                startRefreshTimer();
            }
            
            // Add smooth scroll behavior
            document.documentElement.style.scrollBehavior = 'smooth';
            
            // Add intersection observer for fade-in animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // Observe all media cards for animations
            document.querySelectorAll('.media-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
                observer.observe(card);
            });
        });
        
        // Pause auto-refresh when user is inactive
        let userActive = true;
        let inactivityTimer;
        
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            userActive = true;
            
            inactivityTimer = setTimeout(() => {
                userActive = false;
                // Could pause refresh here if desired
            }, 300000); // 5 minutes
        }
        
        // Listen for user activity
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetInactivityTimer, true);
        });
        
        resetInactivityTimer();
    </script>
</body>
</html>