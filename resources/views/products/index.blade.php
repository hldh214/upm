@extends('layouts.app')

@section('title', 'UPM - Product List')

@section('content')
<div x-data="productList()" x-init="init()">
    <!-- Hero Section - Price Drop Highlight -->
    <div class="bg-gradient-to-r from-red-500 to-orange-500 rounded-lg shadow-lg p-6 mb-6 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">{{ __('ui.hero_title') }}</h1>
                <p class="text-red-100">{{ __('ui.hero_subtitle') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="bg-white/20 backdrop-blur rounded-lg px-4 py-3 text-center">
                    <p class="text-3xl font-bold" x-text="stats.dropped_count?.toLocaleString() || '—'"></p>
                    <p class="text-xs text-red-100">{{ __('ui.price_drops_count') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Filter Tabs -->
    <div class="bg-white rounded-lg shadow mb-4">
        <div class="flex border-b">
            <button
                @click="setQuickFilter('dropped')"
                :class="filters.priceDropped && !filters.priceRaised ? 'border-red-500 text-red-600 bg-red-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                class="flex-1 md:flex-none px-6 py-3 text-sm font-medium border-b-2 transition-colors flex items-center justify-center gap-2"
            >
                <span class="text-red-500">▼</span>
                <span>{{ __('ui.tab_price_drops') }}</span>
            </button>
            <button
                @click="setQuickFilter('raised')"
                :class="filters.priceRaised && !filters.priceDropped ? 'border-green-500 text-green-600 bg-green-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                class="flex-1 md:flex-none px-6 py-3 text-sm font-medium border-b-2 transition-colors flex items-center justify-center gap-2"
            >
                <span class="text-green-500">▲</span>
                <span>{{ __('ui.tab_price_rises') }}</span>
            </button>
            <button
                @click="setQuickFilter('all')"
                :class="!filters.priceDropped && !filters.priceRaised ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                class="flex-1 md:flex-none px-6 py-3 text-sm font-medium border-b-2 transition-colors flex items-center justify-center gap-2"
            >
                <span>{{ __('ui.tab_all_products') }}</span>
            </button>
        </div>
        
        <!-- Time Range Pills (show when price filter is active) -->
        <div x-show="filters.priceDropped || filters.priceRaised" x-cloak class="p-3 bg-gray-50 border-b">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-gray-600">{{ __('ui.time_range') }}:</span>
                <template x-for="day in [1, 3, 7, 14, 30]" :key="day">
                    <button
                        @click="filters.changeDays = day; search()"
                        :class="filters.changeDays == day ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                        class="px-3 py-1 text-sm rounded-full border transition-colors"
                        x-text="day + ' ' + (day === 1 ? '{{ __('ui.day') }}' : '{{ __('ui.days') }}')"
                    ></button>
                </template>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.search') }}</label>
                <input
                    type="text"
                    x-model="filters.q"
                    @input.debounce.300ms="search()"
                    placeholder="{{ __('ui.search_placeholder') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border"
                >
            </div>
            
            <!-- Brand Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.brand') }}</label>
                <select
                    x-model="filters.brand"
                    @change="search()"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border"
                >
                    <option value="">{{ __('ui.all_brands') }}</option>
                    <option value="uniqlo">UNIQLO</option>
                    <option value="gu">GU</option>
                </select>
            </div>
            
            <!-- Gender Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.gender') }}</label>
                <select
                    x-model="filters.gender"
                    @change="search()"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border"
                >
                    <option value="">{{ __('ui.all_genders') }}</option>
                    <option value="MEN">MEN</option>
                    <option value="WOMEN">WOMEN</option>
                    <option value="KIDS">KIDS</option>
                    <option value="BABY">BABY</option>
                    <option value="UNISEX">UNISEX</option>
                </select>
            </div>
        </div>
        
        <!-- Sort -->
        <div class="mt-4 flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">{{ __('ui.sort_by') }}:</label>
            <select
                x-model="filters.sort"
                @change="search()"
                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border"
            >
                <option value="">{{ __('ui.sort_latest') }}</option>
                <option value="price_asc">{{ __('ui.sort_price_low') }}</option>
                <option value="price_desc">{{ __('ui.sort_price_high') }}</option>
                <option value="drop_percent" x-show="filters.priceDropped || filters.priceRaised">{{ __('ui.sort_change_percent') }}</option>
                <option value="name">{{ __('ui.sort_name') }}</option>
                <option value="updated">{{ __('ui.sort_updated') }}</option>
            </select>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" x-show="stats.total_products > 0">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">{{ __('ui.total_products') }}</p>
            <p class="text-2xl font-bold" x-text="stats.total_products?.toLocaleString()"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">UNIQLO</p>
            <p class="text-2xl font-bold text-red-600" x-text="stats.uniqlo_count?.toLocaleString()"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">GU</p>
            <p class="text-2xl font-bold text-blue-600" x-text="stats.gu_count?.toLocaleString()"></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">{{ __('ui.showing') }}</p>
            <p class="text-2xl font-bold" x-text="products.length + ' / ' + pagination.total"></p>
        </div>
    </div>

    <!-- Section Header -->
    <h2 class="text-xl font-bold text-gray-900 mb-4" x-text="getSectionTitle()"></h2>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
        <p class="mt-2 text-gray-600">{{ __('ui.loading') }}</p>
    </div>

    <!-- Product Grid -->
    <div x-show="!loading" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="product in products" :key="product.id">
            <a :href="'/products/' + product.id" 
               class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow"
               :class="{ 'ring-2 ring-red-200': product.price_change_type === 'dropped', 'ring-2 ring-green-200': product.price_change_type === 'raised' }">
                <!-- Product Image -->
                <div class="aspect-square bg-gray-200 relative">
                    <img
                        :src="product.image_url || 'https://via.placeholder.com/300'"
                        :alt="product.name"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    >
                    <!-- Brand Badge -->
                    <span
                        class="absolute top-2 left-2 px-2 py-1 text-xs font-semibold rounded"
                        :class="product.brand === 'uniqlo' ? 'bg-red-600 text-white' : 'bg-blue-600 text-white'"
                        x-text="product.brand.toUpperCase()"
                    ></span>
                    <!-- Gender Badge -->
                    <span
                        x-show="product.gender"
                        class="absolute top-2 right-2 px-2 py-1 text-xs bg-gray-800 text-white rounded"
                        x-text="product.gender"
                    ></span>
                    <!-- Price Change Badge -->
                    <span
                        x-show="product.price_change_percent"
                        class="absolute bottom-2 right-2 px-2 py-1 text-xs font-bold rounded"
                        :class="product.price_change_type === 'dropped' ? 'bg-red-500 text-white' : 'bg-green-500 text-white'"
                    >
                        <span x-text="product.price_change_type === 'dropped' ? '▼' : '▲'"></span>
                        <span x-text="product.price_change_percent + '%'"></span>
                    </span>
                </div>
                
                <!-- Product Info -->
                <div class="p-4">
                    <h3 class="font-medium text-gray-900 line-clamp-2 mb-2" x-text="product.name"></h3>
                    
                    <!-- Price Display - Changes based on price change filter -->
                    <template x-if="product.price_change_type">
                        <div>
                            <div class="flex items-baseline gap-2 flex-wrap">
                                <span class="text-xl font-bold" 
                                      :class="product.price_change_type === 'dropped' ? 'text-red-600' : 'text-green-600'" 
                                      x-text="'¥' + product.current_price.toLocaleString()"></span>
                                <span class="text-sm text-gray-400 line-through" x-text="'¥' + product.previous_price.toLocaleString()"></span>
                            </div>
                            <div class="mt-1 text-xs font-medium"
                                 :class="product.price_change_type === 'dropped' ? 'text-red-600' : 'text-green-600'">
                                <span x-text="(product.price_change_type === 'dropped' ? '-' : '+') + '¥' + product.price_change_amount.toLocaleString()"></span>
                            </div>
                        </div>
                    </template>
                    
                    <!-- Normal Price Display -->
                    <template x-if="!product.price_change_type">
                        <div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-xl font-bold text-gray-900" x-text="'¥' + product.current_price.toLocaleString()"></span>
                                <span
                                    x-show="product.current_price < product.highest_price"
                                    class="text-sm text-green-600"
                                >
                                    <span x-text="'-' + Math.round((1 - product.current_price / product.highest_price) * 100) + '%'"></span>
                                </span>
                            </div>
                            
                            <div class="mt-2 text-xs text-gray-500">
                                <span>{{ __('ui.lowest') }}: ¥<span x-text="product.lowest_price.toLocaleString()"></span></span>
                                <span class="mx-1">|</span>
                                <span>{{ __('ui.highest') }}: ¥<span x-text="product.highest_price.toLocaleString()"></span></span>
                            </div>
                        </div>
                    </template>
                </div>
            </a>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && products.length === 0" class="text-center py-12">
        <p class="text-gray-500">{{ __('ui.no_products') }}</p>
    </div>

    <!-- Google-style Pagination -->
    <div x-show="pagination.last_page > 1" class="mt-8 flex justify-center items-center">
        <nav class="flex items-center gap-1" aria-label="Pagination">
            <!-- Previous Button -->
            <button
                @click="goToPage(pagination.current_page - 1)"
                :disabled="pagination.current_page === 1"
                class="px-3 py-2 text-sm text-blue-600 hover:bg-gray-100 rounded disabled:text-gray-400 disabled:cursor-not-allowed"
            >
                « {{ __('ui.prev') }}
            </button>
            
            <!-- Page Numbers -->
            <template x-for="(item, index) in getPageNumbers()" :key="'page-' + index">
                <button
                    @click="item !== '...' && goToPage(item)"
                    :disabled="item === '...'"
                    :class="{
                        'bg-blue-600 text-white': item === pagination.current_page,
                        'text-blue-600 hover:bg-gray-100': item !== pagination.current_page && item !== '...',
                        'text-gray-400 cursor-default': item === '...'
                    }"
                    class="px-3 py-2 text-sm rounded min-w-[40px]"
                    x-text="item"
                ></button>
            </template>
            
            <!-- Next Button -->
            <button
                @click="goToPage(pagination.current_page + 1)"
                :disabled="pagination.current_page === pagination.last_page"
                class="px-3 py-2 text-sm text-blue-600 hover:bg-gray-100 rounded disabled:text-gray-400 disabled:cursor-not-allowed"
            >
                {{ __('ui.next') }} »
            </button>
        </nav>
    </div>
    
    <!-- Page Info -->
    <div x-show="pagination.last_page > 1" class="mt-2 text-center text-sm text-gray-500">
        {{ __('ui.page') }} <span x-text="pagination.current_page"></span> {{ __('ui.of') }} <span x-text="pagination.last_page"></span>
        (<span x-text="pagination.total.toLocaleString()"></span> {{ __('ui.items') }})
    </div>
</div>
@endsection

@push('scripts')
<script>
function productList() {
    return {
        products: [],
        stats: {},
        loading: true,
        filters: {
            q: '',
            brand: '',
            gender: '',
            sort: '',
            page: 1,
            per_page: 20,
            priceDropped: true,  // Default to price drops
            priceRaised: false,
            changeDays: 3  // Default to 3 days
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0
        },

        async init() {
            // Parse URL parameters on load (may override defaults)
            this.parseUrlParams();
            
            await Promise.all([
                this.fetchProducts(),
                this.fetchStats()
            ]);
            
            // Listen for browser back/forward
            window.addEventListener('popstate', () => {
                this.parseUrlParams();
                this.fetchProducts();
            });
        },
        
        setQuickFilter(type) {
            if (type === 'dropped') {
                this.filters.priceDropped = true;
                this.filters.priceRaised = false;
            } else if (type === 'raised') {
                this.filters.priceDropped = false;
                this.filters.priceRaised = true;
            } else {
                this.filters.priceDropped = false;
                this.filters.priceRaised = false;
            }
            this.search();
        },
        
        getSectionTitle() {
            if (this.filters.priceDropped && !this.filters.priceRaised) {
                return window.translations.section_drops;
            } else if (this.filters.priceRaised && !this.filters.priceDropped) {
                return window.translations.section_rises;
            }
            return window.translations.section_all;
        },

        parseUrlParams() {
            const params = new URLSearchParams(window.location.search);
            
            // Only override defaults if URL has parameters
            if (params.toString()) {
                this.filters.page = parseInt(params.get('page')) || 1;
                this.filters.q = params.get('q') || '';
                this.filters.brand = params.get('brand') || '';
                this.filters.gender = params.get('gender') || '';
                this.filters.sort = params.get('sort') || '';
                
                // Parse price change filters
                const priceChange = params.get('price_change');
                if (priceChange) {
                    const changes = priceChange.split(',');
                    this.filters.priceDropped = changes.includes('dropped');
                    this.filters.priceRaised = changes.includes('raised');
                } else {
                    // If URL exists but no price_change, show all products
                    this.filters.priceDropped = false;
                    this.filters.priceRaised = false;
                }
                this.filters.changeDays = parseInt(params.get('change_days')) || 3;
            }
            // If no URL params, keep defaults (priceDropped: true, changeDays: 3)
        },

        updateUrl() {
            const params = new URLSearchParams();
            if (this.filters.page > 1) params.set('page', this.filters.page);
            if (this.filters.q) params.set('q', this.filters.q);
            if (this.filters.brand) params.set('brand', this.filters.brand);
            if (this.filters.gender) params.set('gender', this.filters.gender);
            if (this.filters.sort) params.set('sort', this.filters.sort);
            
            // Add price change params
            const priceChanges = [];
            if (this.filters.priceDropped) priceChanges.push('dropped');
            if (this.filters.priceRaised) priceChanges.push('raised');
            if (priceChanges.length > 0) {
                params.set('price_change', priceChanges.join(','));
                params.set('change_days', this.filters.changeDays);
            }
            
            const newUrl = params.toString() 
                ? window.location.pathname + '?' + params.toString()
                : window.location.pathname;
            
            window.history.pushState({}, '', newUrl);
        },

        async fetchProducts() {
            this.loading = true;
            
            const params = new URLSearchParams();
            if (this.filters.q) params.append('q', this.filters.q);
            if (this.filters.brand) params.append('brand', this.filters.brand);
            if (this.filters.gender) params.append('gender', this.filters.gender);
            if (this.filters.sort) params.append('sort', this.filters.sort);
            params.append('page', this.filters.page);
            params.append('per_page', this.filters.per_page);
            
            // Add price change params
            const priceChanges = [];
            if (this.filters.priceDropped) priceChanges.push('dropped');
            if (this.filters.priceRaised) priceChanges.push('raised');
            if (priceChanges.length > 0) {
                params.append('price_change', priceChanges.join(','));
                params.append('change_days', this.filters.changeDays);
            }

            try {
                const response = await fetch('/api/products?' + params.toString());
                const data = await response.json();
                
                this.products = data.data;
                this.pagination = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    total: data.total
                };
            } catch (error) {
                console.error('Failed to fetch products:', error);
            } finally {
                this.loading = false;
            }
        },

        async fetchStats() {
            try {
                const response = await fetch('/api/products/stats');
                this.stats = await response.json();
            } catch (error) {
                console.error('Failed to fetch stats:', error);
            }
        },

        search() {
            this.filters.page = 1;
            this.updateUrl();
            this.fetchProducts();
        },

        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.filters.page = page;
            this.updateUrl();
            this.fetchProducts();
            // Scroll to top of product list
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /**
         * Generate Google-style page numbers array
         */
        getPageNumbers() {
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            const delta = 2;
            const pages = [];
            
            if (last <= 9) {
                for (let i = 1; i <= last; i++) {
                    pages.push(i);
                }
                return pages;
            }
            
            pages.push(1);
            if (last > 1) pages.push(2);
            
            let rangeStart = Math.max(3, current - delta);
            let rangeEnd = Math.min(last - 2, current + delta);
            
            if (current <= 4) {
                rangeEnd = Math.max(rangeEnd, 5);
            }
            if (current >= last - 3) {
                rangeStart = Math.min(rangeStart, last - 4);
            }
            
            if (rangeStart > 3) {
                pages.push('...');
            }
            
            for (let i = rangeStart; i <= rangeEnd; i++) {
                if (i > 2 && i < last - 1) {
                    pages.push(i);
                }
            }
            
            if (rangeEnd < last - 2) {
                pages.push('...');
            }
            
            if (last > 2) pages.push(last - 1);
            pages.push(last);
            
            return pages;
        }
    };
}
</script>
@endpush
