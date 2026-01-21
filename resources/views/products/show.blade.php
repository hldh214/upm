@extends('layouts.app')

@section('title', $product->name . ' - UPM')

@section('content')
<div x-data="productDetail()">
    <!-- Back Button -->
    <div class="mb-4">
        <a href="{{ route('home') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to list
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Product Image -->
        <div class="bg-white rounded-lg shadow p-4">
            <div class="aspect-square bg-gray-200 rounded-lg overflow-hidden">
                <img
                    src="{{ $product->image_url ?: 'https://via.placeholder.com/500' }}"
                    alt="{{ $product->name }}"
                    class="w-full h-full object-cover"
                >
            </div>
        </div>

        <!-- Product Info -->
        <div>
            <div class="bg-white rounded-lg shadow p-6">
                <!-- Brand Badge -->
                <span class="inline-block px-3 py-1 text-sm font-semibold rounded mb-4 {{ $product->brand === 'uniqlo' ? 'bg-red-600 text-white' : 'bg-blue-600 text-white' }}">
                    {{ strtoupper($product->brand) }}
                </span>
                @if($product->gender)
                <span class="inline-block px-3 py-1 text-sm bg-gray-200 rounded mb-4 ml-2">
                    {{ $product->gender }}
                </span>
                @endif

                <!-- Product Name -->
                <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>

                <!-- Price Info -->
                <div class="space-y-3 mb-6">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-gray-900">¥{{ number_format($product->current_price) }}</span>
                        @if($product->current_price < $product->highest_price)
                        <span class="text-lg text-green-600 font-medium">
                            -{{ round((1 - $product->current_price / $product->highest_price) * 100) }}% OFF
                        </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="bg-green-50 p-3 rounded">
                            <p class="text-green-600 font-medium">Lowest Price</p>
                            <p class="text-xl font-bold text-green-700">¥{{ number_format($product->lowest_price) }}</p>
                        </div>
                        <div class="bg-red-50 p-3 rounded">
                            <p class="text-red-600 font-medium">Highest Price</p>
                            <p class="text-xl font-bold text-red-700">¥{{ number_format($product->highest_price) }}</p>
                        </div>
                    </div>

                    @if($product->current_price <= $product->lowest_price)
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded">
                        <strong>Now at the lowest price!</strong>
                    </div>
                    @endif
                </div>

                <!-- Product ID -->
                <div class="text-sm text-gray-500 mb-4">
                    <p>Product ID: {{ $product->product_id }}</p>
                    <p>Price Group: {{ $product->price_group }}</p>
                </div>

                <!-- View on Official Site -->
                <a
                    href="{{ $product->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
                >
                    View on Official Site
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Price History Chart -->
    <div class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Price History</h2>

        <!-- Time Range Selector -->
        <div class="flex gap-2 mb-4">
            <button
                @click="fetchHistory(30)"
                :class="days === 30 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-4 py-2 rounded-md transition-colors"
            >
                30 Days
            </button>
            <button
                @click="fetchHistory(90)"
                :class="days === 90 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-4 py-2 rounded-md transition-colors"
            >
                90 Days
            </button>
            <button
                @click="fetchHistory(180)"
                :class="days === 180 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-4 py-2 rounded-md transition-colors"
            >
                180 Days
            </button>
            <button
                @click="fetchHistory(365)"
                :class="days === 365 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-4 py-2 rounded-md transition-colors"
            >
                1 Year
            </button>
        </div>

        <div class="relative h-80">
            <canvas id="priceChart"></canvas>
        </div>

        <div x-show="history.length === 0" class="text-center py-8 text-gray-500">
            No price history available yet
        </div>
    </div>

    <!-- Price History Table -->
    <div x-show="history.length > 0" class="mt-8 bg-white rounded-lg shadow overflow-hidden">
        <h2 class="text-xl font-bold text-gray-900 p-6 pb-4">Price History Details</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Change</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(item, index) in history.slice().reverse()" :key="item.date">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="item.date"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="'¥' + item.price.toLocaleString()"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <template x-if="index < history.length - 1">
                                    <span
                                        :class="{
                                            'text-green-600': history[history.length - index - 1].price < history[history.length - index - 2]?.price,
                                            'text-red-600': history[history.length - index - 1].price > history[history.length - index - 2]?.price,
                                            'text-gray-400': history[history.length - index - 1].price === history[history.length - index - 2]?.price
                                        }"
                                        x-text="getPriceChange(history.length - index - 1)"
                                    ></span>
                                </template>
                                <template x-if="index === history.length - 1">
                                    <span class="text-gray-400">-</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function productDetail() {
    return {
        productId: {{ $product->id }},
        history: [],
        chart: null,
        days: 90,

        async init() {
            await this.fetchHistory(90);
        },

        async fetchHistory(days) {
            this.days = days;
            
            try {
                const response = await fetch(`/api/products/${this.productId}/history?days=${days}`);
                const data = await response.json();
                this.history = data.history;
                this.renderChart();
            } catch (error) {
                console.error('Failed to fetch history:', error);
            }
        },

        renderChart() {
            const ctx = document.getElementById('priceChart');
            
            if (this.chart) {
                this.chart.destroy();
            }

            if (this.history.length === 0) return;

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.history.map(h => h.date),
                    datasets: [{
                        label: 'Price (¥)',
                        data: this.history.map(h => h.price),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.1,
                        pointRadius: 3,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '¥' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return '¥' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        },

        getPriceChange(index) {
            if (index === 0) return '-';
            
            const current = this.history[index].price;
            const previous = this.history[index - 1].price;
            const diff = current - previous;
            
            if (diff === 0) return '→ ¥0';
            if (diff > 0) return '↑ ¥' + diff.toLocaleString();
            return '↓ ¥' + Math.abs(diff).toLocaleString();
        }
    };
}
</script>
@endpush
