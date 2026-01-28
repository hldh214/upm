<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'UPM - Uniqlo/GU Price Monitor')</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'uq-red': '#FF0000',
                        'gu-blue': '#0033FF',
                    },
                    fontFamily: {
                        sans: ['-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Day.js for timezone-aware date formatting -->
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/utc.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/timezone.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/relativeTime.js"></script>

    <!-- Alpine.js with date formatting magic helpers -->
    <script>
        // Setup Day.js plugins
        dayjs.extend(dayjs_plugin_utc);
        dayjs.extend(dayjs_plugin_timezone);
        dayjs.extend(dayjs_plugin_relativeTime);

        const userTz = dayjs.tz.guess();

        // Global helpers (for use in regular JS, e.g., Chart.js)
        window.formatDate = (iso, fmt = 'YYYY-MM-DD HH:mm:ss') => dayjs(iso).tz(userTz).format(fmt);
        window.formatDateRelative = (iso) => dayjs(iso).tz(userTz).fromNow();
        
        // Global translations from Laravel
        window.translations = @json(__('ui'));
        window.currentLang = '{{ app()->getLocale() }}';

        // Register Alpine.js magic helpers before Alpine loads
        document.addEventListener('alpine:init', () => {
            // $date(isoString, format?) - format UTC timestamp to local time
            Alpine.magic('date', () => (iso, fmt = 'YYYY-MM-DD HH:mm:ss') => dayjs(iso).tz(userTz).format(fmt));

            // $dateRelative(isoString) - "3 hours ago", "2 days ago"
            Alpine.magic('dateRelative', () => (iso) => dayjs(iso).tz(userTz).fromNow());
            
            // $t(key) - get translation
            Alpine.magic('t', () => (key) => window.translations[key] || key);
        });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* UNIQLO-inspired clean styles */
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Hide scrollbar but keep functionality */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        /* Sale price animation */
        @keyframes pulse-sale {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        .animate-sale {
            animation: pulse-sale 2s ease-in-out infinite;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-white min-h-screen flex flex-col">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200" x-data="languageSwitcher()">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between h-14 px-4">
                <!-- Logo & Tagline -->
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="text-xl font-bold tracking-tight">UPM</span>
                    <span class="hidden sm:inline-block text-xs text-gray-500 border-l border-gray-300 pl-2">{{ __('ui.tagline') }}</span>
                </a>
                
                <!-- Right Side -->
                <div class="flex items-center gap-3">
                    <!-- Language Switcher -->
                    <div class="relative">
                        <button
                            @click="open = !open"
                            class="flex items-center gap-1 px-2 py-1.5 text-sm text-gray-600 hover:text-black transition-colors"
                        >
                            <span x-text="currentLang.toUpperCase()" class="font-medium"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- Dropdown -->
                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-1 w-32 bg-white border border-gray-200 shadow-lg z-50"
                        >
                            <template x-for="(name, code) in languages" :key="code">
                                <button
                                    @click="setLanguage(code)"
                                    :class="currentLang === code ? 'bg-gray-50 font-medium' : 'hover:bg-gray-50'"
                                    class="w-full px-4 py-2.5 text-sm text-left text-gray-700"
                                    x-text="name"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto py-6 px-4">
            <p class="text-center text-xs text-gray-400">
                {{ __('ui.footer_text') }}
            </p>
        </div>
    </footer>

    <script>
    function languageSwitcher() {
        return {
            open: false,
            currentLang: window.currentLang || 'en',
            languages: {
                'en': 'English',
                'ja': '日本語'
            },
            
            setLanguage(lang) {
                if (this.currentLang === lang) {
                    this.open = false;
                    return;
                }
                
                // Set cookie and reload page to get new translations from server
                document.cookie = `upm_language=${lang};path=/;max-age=${60*60*24*365}`;
                localStorage.setItem('upm_language', lang);
                window.location.reload();
            }
        };
    }
    </script>

    @stack('scripts')
</body>
</html>
