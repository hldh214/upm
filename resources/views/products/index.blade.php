@extends('layouts.app')

@section('title', 'UPM - UNIQLO & GU Price Monitor')

@section('content')
<div x-data="productList()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <!-- Category Tabs - Minimal design -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex gap-6 -mb-px overflow-x-auto hide-scrollbar">
                <button
                    @click="setQuickFilter('dropped')"
                    :class="filters.priceDropped && !filters.priceRaised 
                        ? 'border-black text-black' 
                        : 'border-transparent text-gray-500 hover:text-black'"
                    class="pb-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
                >
                    {{ __('ui.tab_price_drops') }}
                </button>
                <button
                    @click="setQuickFilter('all')"
                    :class="!filters.priceDropped && !filters.priceRaised 
                        ? 'border-black text-black' 
                        : 'border-transparent text-gray-500 hover:text-black'"
                    class="pb-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
                >
                    {{ __('ui.tab_all_products') }}
                </button>
                <button
                    @click="setQuickFilter('raised')"
                    :class="filters.priceRaised && !filters.priceDropped 
                        ? 'border-black text-black' 
                        : 'border-transparent text-gray-500 hover:text-black'"
                    class="pb-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
                >
                    {{ __('ui.tab_price_rises') }}
                </button>
            </nav>
        </div>

        <!-- Time Range - Pill buttons -->
        <div x-show="filters.priceDropped || filters.priceRaised" x-cloak class="mb-6">
            <div class="flex flex-wrap gap-2">
                <template x-for="day in [1, 3, 7, 14, 30]" :key="day">
                    <button
                        @click="filters.changeDays = day; search()"
                        :class="filters.changeDays == day 
                            ? 'bg-black text-white' 
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-1.5 text-xs font-medium transition-colors"
                        x-text="day + (day === 1 ? '{{ __('ui.day') }}' : '{{ __('ui.days') }}')"
                    ></button>
                </template>
            </div>
        </div>

        <!-- Filters Row - Compact horizontal layout -->
        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <!-- Search -->
            <div class="flex-1">
                <div class="relative">
                    <input
                        type="text"
                        x-model="filters.q"
                        @input.debounce.300ms="search()"
                        placeholder="{{ __('ui.search_placeholder') }}"
                        class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none"
                    >
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            
            <!-- Filter Dropdowns -->
            <div class="flex gap-2">
                <select
                    x-model="filters.brand"
                    @change="search()"
                    class="px-3 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none bg-white min-w-[100px]"
                >
                    <option value="">{{ __('ui.all_brands') }}</option>
                    <option value="uniqlo">UNIQLO</option>
                    <option value="gu">GU</option>
                </select>
                
                <select
                    x-model="filters.gender"
                    @change="search()"
                    class="px-3 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none bg-white min-w-[100px]"
                >
                    <option value="">{{ __('ui.all_genders') }}</option>
                    <option value="MEN">MEN</option>
                    <option value="WOMEN">WOMEN</option>
                    <option value="KIDS">KIDS</option>
                    <option value="BABY">BABY</option>
                </select>
                
                <select
                    x-model="filters.sort"
                    @change="search()"
                    class="px-3 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none bg-white min-w-[120px]"
                >
                    <option value="">{{ __('ui.sort_latest') }}</option>
                    <option value="price_asc">{{ __('ui.sort_price_low') }}</option>
                    <option value="price_desc">{{ __('ui.sort_price_high') }}</option>
                    <option value="drop_percent" x-show="filters.priceDropped || filters.priceRaised">{{ __('ui.sort_change_percent') }}</option>
                </select>
            </div>
        </div>

        <!-- Results Count -->
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">
                <span x-text="pagination.total.toLocaleString()"></span> {{ __('ui.items') }}
            </p>
        </div>

        <!-- Loading -->
        <div x-show="loading" class="flex items-center justify-center py-20">
            <div class="w-6 h-6 border-2 border-gray-300 border-t-black rounded-full animate-spin"></div>
        </div>

        <!-- Product Grid - Clean card layout -->
        <div x-show="!loading" x-cloak class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-px bg-gray-200">
            <template x-for="product in products" :key="product.id">
                <a :href="'/products/' + product.id" class="bg-white block group">
                    <!-- Image Container -->
                    <div class="relative aspect-square bg-gray-50 overflow-hidden">
                        <img
                            :src="product.image_url || 'https://via.placeholder.com/400?text=No+Image'"
                            :alt="product.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        >
                        
                        <!-- Sale Badge - Top left, UNIQLO style -->
                        <div x-show="product.price_change_type === 'dropped'" 
                             class="absolute top-0 left-0 bg-uq-red text-white px-2 py-1">
                            <span class="text-xs font-bold">SALE</span>
                        </div>
                        
                        <!-- Brand indicator - Small dot -->
                        <div class="absolute top-2 right-2">
                            <span 
                                class="inline-block w-2 h-2 rounded-full"
                                :class="product.brand === 'uniqlo' ? 'bg-uq-red' : 'bg-gu-blue'"
                            ></span>
                        </div>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="p-3">
                        <!-- Brand & Gender -->
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-medium text-gray-400 uppercase" x-text="product.brand"></span>
                            <span x-show="product.gender" class="text-[10px] text-gray-300">|</span>
                            <span x-show="product.gender" class="text-[10px] text-gray-400" x-text="product.gender"></span>
                        </div>
                        
                        <!-- Product Name -->
                        <h3 class="text-xs text-gray-900 line-clamp-2 mb-2 leading-relaxed" x-text="product.name"></h3>
                        
                        <!-- Price Section -->
                        <div class="space-y-0.5">
                            <!-- Sale Price Display -->
                            <template x-if="product.price_change_type === 'dropped'">
                                <div>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-sm font-bold text-uq-red" x-text="'¥' + product.current_price.toLocaleString()"></span>
                                        <span class="text-xs text-gray-400 line-through" x-text="'¥' + product.previous_price.toLocaleString()"></span>
                                    </div>
                                    <div class="text-[10px] text-uq-red font-medium">
                                        <span x-text="'-' + product.price_change_percent + '%'"></span>
                                        <span class="text-gray-400 ml-1" x-text="'(-¥' + product.price_change_amount.toLocaleString() + ')'"></span>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Price Rise Display -->
                            <template x-if="product.price_change_type === 'raised'">
                                <div>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-sm font-bold text-gray-900" x-text="'¥' + product.current_price.toLocaleString()"></span>
                                        <span class="text-xs text-gray-400 line-through" x-text="'¥' + product.previous_price.toLocaleString()"></span>
                                    </div>
                                    <div class="text-[10px] text-gray-500">
                                        <span x-text="'+' + product.price_change_percent + '%'"></span>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Normal Price Display -->
                            <template x-if="!product.price_change_type">
                                <div>
                                    <span class="text-sm font-bold text-gray-900" x-text="'¥' + product.current_price.toLocaleString()"></span>
                                    <!-- Show if below highest -->
                                    <template x-if="product.current_price < product.highest_price">
                                        <div class="text-[10px] text-gray-400">
                                            {{ __('ui.highest') }}: <span x-text="'¥' + product.highest_price.toLocaleString()"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </a>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && products.length === 0" class="text-center py-20">
            <p class="text-gray-400 text-sm">{{ __('ui.no_products') }}</p>
        </div>

        <!-- Pagination - Minimal style -->
        <div x-show="pagination.last_page > 1" class="mt-8 flex justify-center">
            <nav class="flex items-center gap-1">
                <!-- Previous -->
                <button
                    @click="goToPage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                
                <!-- Page Numbers -->
                <template x-for="(item, index) in getPageNumbers()" :key="'page-' + index">
                    <button
                        @click="item !== '...' && goToPage(item)"
                        :disabled="item === '...'"
                        :class="{
                            'bg-black text-white': item === pagination.current_page,
                            'hover:bg-gray-100': item !== pagination.current_page && item !== '...',
                            'cursor-default': item === '...'
                        }"
                        class="w-10 h-10 flex items-center justify-center text-sm transition-colors"
                        x-text="item"
                    ></button>
                </template>
                
                <!-- Next -->
                <button
                    @click="goToPage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </nav>
        </div>
        
        <!-- Page indicator -->
        <div x-show="pagination.last_page > 1" class="mt-3 text-center">
            <span class="text-xs text-gray-400">
                <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
            </span>
        </div>
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
            per_page: 24,
            priceDropped: true,
            priceRaised: false,
            changeDays: 3
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0
        },

        async init() {
            this.parseUrlParams();
            
            await Promise.all([
                this.fetchProducts(),
                this.fetchStats()
            ]);
            
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

        parseUrlParams() {
            const params = new URLSearchParams(window.location.search);
            
            if (params.toString()) {
                this.filters.page = parseInt(params.get('page')) || 1;
                this.filters.q = params.get('q') || '';
                this.filters.brand = params.get('brand') || '';
                this.filters.gender = params.get('gender') || '';
                this.filters.sort = params.get('sort') || '';
                
                const priceChange = params.get('price_change');
                if (priceChange) {
                    const changes = priceChange.split(',');
                    this.filters.priceDropped = changes.includes('dropped');
                    this.filters.priceRaised = changes.includes('raised');
                } else {
                    this.filters.priceDropped = false;
                    this.filters.priceRaised = false;
                }
                this.filters.changeDays = parseInt(params.get('change_days')) || 3;
            }
        },

        updateUrl() {
            const params = new URLSearchParams();
            if (this.filters.page > 1) params.set('page', this.filters.page);
            if (this.filters.q) params.set('q', this.filters.q);
            if (this.filters.brand) params.set('brand', this.filters.brand);
            if (this.filters.gender) params.set('gender', this.filters.gender);
            if (this.filters.sort) params.set('sort', this.filters.sort);
            
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
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        getPageNumbers() {
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            const delta = 2;
            const pages = [];
            
            if (last <= 7) {
                for (let i = 1; i <= last; i++) {
                    pages.push(i);
                }
                return pages;
            }
            
            pages.push(1);
            
            let rangeStart = Math.max(2, current - delta);
            let rangeEnd = Math.min(last - 1, current + delta);
            
            if (current <= 3) {
                rangeEnd = 5;
            }
            if (current >= last - 2) {
                rangeStart = last - 4;
            }
            
            if (rangeStart > 2) {
                pages.push('...');
            }
            
            for (let i = rangeStart; i <= rangeEnd; i++) {
                pages.push(i);
            }
            
            if (rangeEnd < last - 1) {
                pages.push('...');
            }
            
            pages.push(last);
            
            return pages;
        }
    };
}
</script>
@endpush
