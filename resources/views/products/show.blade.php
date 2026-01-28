@extends('layouts.app')

@section('title', $product->name . ' - UPM')

@section('content')
<div x-data="productDetail()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <!-- Breadcrumb -->
        <nav class="mb-4">
            <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-black transition-colors">
                {{ __('ui.back_to_list') }}
            </a>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Product Image -->
            <div class="bg-gray-50">
                <div class="aspect-square relative">
                    <img
                        src="{{ $product->image_url ?: 'https://via.placeholder.com/600?text=No+Image' }}"
                        alt="{{ $product->name }}"
                        class="w-full h-full object-cover"
                    >
                    @if($product->current_price < $product->highest_price)
                    <div class="absolute top-0 left-0 bg-uq-red text-white px-3 py-1.5">
                        <span class="text-xs font-bold">SALE</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Product Info -->
            <div class="py-2">
                <!-- Brand & Gender -->
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-xs font-medium px-2 py-1 {{ $product->brand === 'uniqlo' ? 'bg-uq-red text-white' : 'bg-gu-blue text-white' }}">
                        {{ strtoupper($product->brand) }}
                    </span>
                    @if($product->gender)
                    <span class="text-xs text-gray-400">{{ $product->gender }}</span>
                    @endif
                </div>

                <!-- Product Name -->
                <h1 class="text-xl font-medium text-gray-900 mb-6 leading-relaxed">{{ $product->name }}</h1>

                <!-- Price Section -->
                <div class="mb-8">
                    <div class="flex items-baseline gap-3 mb-2">
                        <span class="text-3xl font-bold {{ $product->current_price < $product->highest_price ? 'text-uq-red' : 'text-gray-900' }}">
                            ¥{{ number_format($product->current_price) }}
                        </span>
                        @if($product->current_price < $product->highest_price)
                        <span class="text-sm text-gray-400 line-through">
                            ¥{{ number_format($product->highest_price) }}
                        </span>
                        <span class="text-sm font-medium text-uq-red">
                            -{{ round((1 - $product->current_price / $product->highest_price) * 100) }}%
                        </span>
                        @endif
                    </div>
                    
                    @if($product->current_price <= $product->lowest_price)
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-50 border border-yellow-200 text-yellow-800 text-xs">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="font-medium">{{ __('ui.lowest_now') }}</span>
                    </div>
                    @endif
                </div>

                <!-- Price Range -->
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="p-4 bg-gray-50">
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-1">{{ __('ui.lowest_price') }}</p>
                        <p class="text-lg font-bold text-gray-900">¥{{ number_format($product->lowest_price) }}</p>
                    </div>
                    <div class="p-4 bg-gray-50">
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-1">{{ __('ui.highest_price') }}</p>
                        <p class="text-lg font-bold text-gray-900">¥{{ number_format($product->highest_price) }}</p>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="border-t border-gray-200 pt-6 mb-8">
                    <dl class="space-y-3 text-sm">
                        <div class="flex">
                            <dt class="w-32 text-gray-400">{{ __('ui.product_id') }}</dt>
                            <dd class="text-gray-900">{{ $product->product_id }}</dd>
                        </div>
                        <div class="flex">
                            <dt class="w-32 text-gray-400">{{ __('ui.price_group') }}</dt>
                            <dd class="text-gray-900">{{ $product->price_group }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- CTA Button -->
                <a
                    href="{{ $product->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center w-full px-6 py-4 bg-black text-white text-sm font-medium hover:bg-gray-800 transition-colors"
                >
                    {{ __('ui.view_official') }}
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Price History Section -->
        <div class="mt-12 border-t border-gray-200 pt-8">
            <h2 class="text-lg font-medium text-gray-900 mb-6">{{ __('ui.price_history') }}</h2>

            <!-- Chart -->
            <div class="bg-gray-50 p-4 mb-8" x-show="history.length > 0">
                <div class="h-64">
                    <canvas id="priceChart"></canvas>
                </div>
            </div>

            <div x-show="history.length === 0" class="text-center py-12 bg-gray-50">
                <p class="text-sm text-gray-400">{{ __('ui.no_history') }}</p>
            </div>

            <!-- History Table -->
            <div x-show="history.length > 0" class="overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('ui.date') }}</th>
                            <th class="text-right py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('ui.price') }}</th>
                            <th class="text-right py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">{{ __('ui.change') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(item, index) in history.slice().reverse()" :key="item.date">
                            <tr>
                                <td class="py-3 text-sm text-gray-600" x-text="$date(item.date, 'YYYY/MM/DD')"></td>
                                <td class="py-3 text-sm text-gray-900 text-right font-medium" x-text="'¥' + item.price.toLocaleString()"></td>
                                <td class="py-3 text-sm text-right">
                                    <template x-if="index < history.length - 1">
                                        <span
                                            :class="{
                                                'text-green-600': history[history.length - index - 1].price < history[history.length - index - 2]?.price,
                                                'text-uq-red': history[history.length - index - 1].price > history[history.length - index - 2]?.price,
                                                'text-gray-300': history[history.length - index - 1].price === history[history.length - index - 2]?.price
                                            }"
                                            x-text="getPriceChange(history.length - index - 1)"
                                        ></span>
                                    </template>
                                    <template x-if="index === history.length - 1">
                                        <span class="text-gray-300">-</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
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
        abortController: null,

        async init() {
            await this.fetchHistory();
        },

        async fetchHistory() {
            if (this.abortController) {
                this.abortController.abort();
            }

            this.abortController = new AbortController();

            try {
                const response = await fetch(
                    `/api/products/${this.productId}/history`,
                    { signal: this.abortController.signal }
                );
                const data = await response.json();
                this.history = data.history;
                this.renderChart();
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Failed to fetch history:', error);
                }
            }
        },

        renderChart() {
            const canvas = document.getElementById('priceChart');
            if (!canvas) return;

            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }

            if (this.history.length === 0) return;

            this.chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: this.history.map(h => formatDate(h.date, 'MM/DD')),
                    datasets: [{
                        label: window.translations.price,
                        data: this.history.map(h => h.price),
                        borderColor: '#000',
                        backgroundColor: 'rgba(0, 0, 0, 0.05)',
                        fill: true,
                        tension: 0,
                        pointRadius: 3,
                        pointBackgroundColor: '#000',
                        pointHoverRadius: 5,
                        borderWidth: 1.5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#000',
                            titleFont: { size: 11 },
                            bodyFont: { size: 11 },
                            padding: 8,
                            displayColors: false,
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
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                font: { size: 10 },
                                color: '#999',
                                callback: function(value) {
                                    return '¥' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: { size: 10 },
                                color: '#999',
                                maxRotation: 0
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

            if (diff === 0) return '→';
            if (diff > 0) return '+¥' + diff.toLocaleString();
            return '-¥' + Math.abs(diff).toLocaleString();
        }
    };
}
</script>
@endpush
