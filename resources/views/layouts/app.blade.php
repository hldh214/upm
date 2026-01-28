<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'UPM - Uniqlo/GU Price Monitor')</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

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
    </style>

    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm" x-data="languageSwitcher()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900">
                        UPM
                    </a>
                    <span class="ml-2 text-sm text-gray-500 hidden sm:inline">{{ __('ui.tagline') }}</span>
                </div>
                
                <!-- Language Switcher -->
                <div class="flex items-center">
                    <div class="relative">
                        <button
                            @click="open = !open"
                            class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span x-text="languages[currentLang]"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- Dropdown -->
                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-cloak
                            class="absolute right-0 mt-2 w-40 bg-white rounded-md shadow-lg border z-50"
                        >
                            <template x-for="(name, code) in languages" :key="code">
                                <button
                                    @click="setLanguage(code)"
                                    :class="currentLang === code ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'"
                                    class="w-full px-4 py-2 text-sm text-left flex items-center gap-2"
                                >
                                    <span x-show="currentLang === code" class="text-blue-600">✓</span>
                                    <span x-show="currentLang !== code" class="w-4"></span>
                                    <span x-text="name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-auto">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500">
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
