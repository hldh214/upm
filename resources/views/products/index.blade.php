@extends('layouts.app')

@section('title', 'UPM - Product List')

@section('content')
<div x-data="productList()" x-init="init()">
    <!-- Price Dropped Section -->
    <div x-show="priceDroppedProducts.length > 0" x-cloak class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <span class="text-red-500">&#x25BC;</span>
                Price Drops
                <span class="text-sm font-normal text-gray-500" x-text="'(' + priceDroppedProducts.length + ' items in last ' + priceDroppedDays + ' days)'"></span>
            </h2>
            <select
                x-model="priceDroppedDays"
                @change="fetchPriceDroppedProducts()"
                class="text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-2 py-1 border"
            >
                <option value="1">Today</option>
                <option value="3">Last 3 days</option>
                <option value="7">Last 7 days</option>
                <option value="14">Last 14 days</option>
            </select>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            <template x-for="product in priceDroppedProducts" :key="'dropped-' + product.id">
                <a :href="'/products/' + product.id" class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow border-2 border-red-100">
                    <!-- Product Image -->
                    <div class="aspect-square bg-gray-200 relative">
                        <img
                            :src="product.image_url || 'https://via.placeholder.com/300'"
                            :alt="product.name"
                            class="w-full h-full object-cover"
                            loading="lazy"
                        >
                        <!-- Price Drop Badge -->
                        <span class="absolute top-2 right-2 px-2 py-1 text-xs font-bold bg-red-500 text-white rounded">
                            -<span x-text="product.drop_percentage"></span>%
                        </span>
                        <!-- Brand Badge -->
                        <span
                            class="absolute top-2 left-2 px-2 py-1 text-xs font-semibold rounded"
                            :class="product.brand === 'uniqlo' ? 'bg-red-600 text-white' : 'bg-blue-600 text-white'"
                            x-text="product.brand.toUpperCase()"
                        ></span>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="p-3">
                        <h3 class="font-medium text-gray-900 text-sm line-clamp-2 mb-2" x-text="product.name"></h3>
                        
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span class="text-lg font-bold text-red-600" x-text="'¥' + product.current_price.toLocaleString()"></span>
                            <span class="text-sm text-gray-400 line-through" x-text="'¥' + product.previous_price.toLocaleString()"></span>
                        </div>
                        <div class="mt-1 text-xs text-green-600 font-medium">
                            <span x-text="'-¥' + product.drop_amount.toLocaleString()"></span>
                        </div>
                    </div>
                </a>
            </template>
        </div>
    </div>

    <!-- Divider -->
    <div x-show="priceDroppedProducts.length > 0" x-cloak class="border-t border-gray-200 mb-8"></div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search Input -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input
                    type="text"
                    x-model="filters.q"
                    @input.debounce.300ms="search()"
                    placeholder="Search by product name or ID..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border"
                >
            </div>
            
            <!-- Brand Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                <select
                    x-model="filters.brand"
                    @change="search()"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border"
                >
                    <option value="">All Brands</option>
                    <option value="uniqlo">UNIQLO</option>
                    <option value="gu">GU</option>
                </select>
            </div>
            
            <!-- Gender Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select
                    x-model="filters.gender"
                    @change="search()"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border"
                >
                    <option value="">All</option>
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
            <label class="text-sm font-medium text-gray-700">Sort by:</label>
            <select
                x-model="filters.sort"
                @change="search()"
                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 px-3 py-2 border"
            >
                <option value="">Latest</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="name">Name</option>
                <option value="updated">Recently Updated</option>
            </select>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" x-show="stats.total_products > 0">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total Products</p>
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
            <p class="text-sm text-gray-500">Showing</p>
            <p class="text-2xl font-bold" x-text="products.length + ' / ' + pagination.total"></p>
        </div>
    </div>

    <!-- All Products Header -->
    <h2 class="text-xl font-bold text-gray-900 mb-4">All Products</h2>

    <!-- Loading -->
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
        <p class="mt-2 text-gray-600">Loading...</p>
    </div>

    <!-- Product Grid -->
    <div x-show="!loading" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="product in products" :key="product.id">
            <a :href="'/products/' + product.id" class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
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
                </div>
                
                <!-- Product Info -->
                <div class="p-4">
                    <h3 class="font-medium text-gray-900 line-clamp-2 mb-2" x-text="product.name"></h3>
                    
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
                        <span>Lowest: ¥<span x-text="product.lowest_price.toLocaleString()"></span></span>
                        <span class="mx-1">|</span>
                        <span>Highest: ¥<span x-text="product.highest_price.toLocaleString()"></span></span>
                    </div>
                </div>
            </a>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && products.length === 0" class="text-center py-12">
        <p class="text-gray-500">No products found</p>
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
                &laquo; Prev
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
                Next &raquo;
            </button>
        </nav>
    </div>
    
    <!-- Page Info -->
    <div x-show="pagination.last_page > 1" class="mt-2 text-center text-sm text-gray-500">
        Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
        (<span x-text="pagination.total.toLocaleString()"></span> items)
    </div>
</div>
@endsection

@push('scripts')
<script>
function productList() {
    return {
        products: [],
        priceDroppedProducts: [],
        priceDroppedDays: 7,
        stats: {},
        loading: true,
        filters: {
            q: '',
            brand: '',
            gender: '',
            sort: '',
            page: 1,
            per_page: 20
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0
        },

        async init() {
            // Parse URL parameters on load
            this.parseUrlParams();
            
            await Promise.all([
                this.fetchProducts(),
                this.fetchStats(),
                this.fetchPriceDroppedProducts()
            ]);
            
            // Listen for browser back/forward
            window.addEventListener('popstate', () => {
                this.parseUrlParams();
                this.fetchProducts();
            });
        },

        parseUrlParams() {
            const params = new URLSearchParams(window.location.search);
            this.filters.page = parseInt(params.get('page')) || 1;
            this.filters.q = params.get('q') || '';
            this.filters.brand = params.get('brand') || '';
            this.filters.gender = params.get('gender') || '';
            this.filters.sort = params.get('sort') || '';
        },

        updateUrl() {
            const params = new URLSearchParams();
            if (this.filters.page > 1) params.set('page', this.filters.page);
            if (this.filters.q) params.set('q', this.filters.q);
            if (this.filters.brand) params.set('brand', this.filters.brand);
            if (this.filters.gender) params.set('gender', this.filters.gender);
            if (this.filters.sort) params.set('sort', this.filters.sort);
            
            const newUrl = params.toString() 
                ? window.location.pathname + '?' + params.toString()
                : window.location.pathname;
            
            window.history.pushState({}, '', newUrl);
        },

        async fetchProducts() {
            this.loading = true;
            
            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });

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

        async fetchPriceDroppedProducts() {
            try {
                const response = await fetch('/api/products/price-dropped?days=' + this.priceDroppedDays + '&limit=12');
                const data = await response.json();
                this.priceDroppedProducts = data.data;
            } catch (error) {
                console.error('Failed to fetch price dropped products:', error);
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
         * Example: 1 2 3 4 5 ... 18 19 (when on page 1-5)
         * Example: 1 2 ... 5 6 7 8 9 ... 18 19 (when in middle)
         * Example: 1 2 ... 15 16 17 18 19 (when near end)
         */
        getPageNumbers() {
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            const delta = 2; // Number of pages to show on each side of current
            const pages = [];
            
            if (last <= 9) {
                // Show all pages if total is small
                for (let i = 1; i <= last; i++) {
                    pages.push(i);
                }
                return pages;
            }
            
            // Always show first two pages
            pages.push(1);
            if (last > 1) pages.push(2);
            
            // Calculate range around current page
            let rangeStart = Math.max(3, current - delta);
            let rangeEnd = Math.min(last - 2, current + delta);
            
            // Adjust range if near start or end
            if (current <= 4) {
                rangeEnd = Math.max(rangeEnd, 5);
            }
            if (current >= last - 3) {
                rangeStart = Math.min(rangeStart, last - 4);
            }
            
            // Add ellipsis before range if needed
            if (rangeStart > 3) {
                pages.push('...');
            }
            
            // Add range pages
            for (let i = rangeStart; i <= rangeEnd; i++) {
                if (i > 2 && i < last - 1) {
                    pages.push(i);
                }
            }
            
            // Add ellipsis after range if needed
            if (rangeEnd < last - 2) {
                pages.push('...');
            }
            
            // Always show last two pages
            if (last > 2) pages.push(last - 1);
            pages.push(last);
            
            return pages;
        }
    };
}
</script>
@endpush
