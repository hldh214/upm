<!DOCTYPE html>
<html lang="ja">
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
        window.formatDate = (iso, fmt = 'YYYY-MM-DD HH:mm') => dayjs(iso).tz(userTz).format(fmt);
        window.formatDateRelative = (iso) => dayjs(iso).tz(userTz).fromNow();
        
        // Register Alpine.js magic helpers before Alpine loads
        document.addEventListener('alpine:init', () => {
            // $date(isoString, format?) - format UTC timestamp to local time
            Alpine.magic('date', () => (iso, fmt = 'YYYY-MM-DD HH:mm') => dayjs(iso).tz(userTz).format(fmt));
            
            // $dateRelative(isoString) - "3 hours ago", "2 days ago"
            Alpine.magic('dateRelative', () => (iso) => dayjs(iso).tz(userTz).fromNow());
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
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900">
                        UPM
                    </a>
                    <span class="ml-2 text-sm text-gray-500">Uniqlo/GU Price Monitor</span>
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
                UPM - Price data is updated daily
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
