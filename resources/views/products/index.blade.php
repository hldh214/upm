@extends('layouts.app')

@section('title', 'UPM - Product List')

@section('content')
<div x-data="productList()" x-init="init()">
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

    <!-- Pagination -->
    <div x-show="pagination.last_page > 1" class="mt-8 flex justify-center gap-2">
        <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-4 py-2 bg-white border rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            Previous
        </button>
        
        <span class="px-4 py-2">
            Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span>
        </span>
        
        <button
            @click="goToPage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-4 py-2 bg-white border rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            Next
        </button>
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
            per_page: 20
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0
        },

        async init() {
            await Promise.all([
                this.fetchProducts(),
                this.fetchStats()
            ]);
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
            this.fetchProducts();
        },

        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.filters.page = page;
            this.fetchProducts();
        }
    };
}
</script>
@endpush
