import { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import axios from 'axios';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Line } from 'react-chartjs-2';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

export default function Show({ product, history, watchlistCount, auth }) {
    const { translations } = usePage().props;
    const t = translations;
    const [isInWatchlist, setIsInWatchlist] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [currentWatchlistCount, setCurrentWatchlistCount] = useState(watchlistCount || 0);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (auth.user) {
            checkWatchlistStatus();
        }
    }, [auth.user, product.id]);

    const checkWatchlistStatus = async () => {
        try {
            const response = await axios.get(`/api/watchlist/check/${product.id}`);
            setIsInWatchlist(response.data.inWatchlist);
        } catch (err) {
            console.error('Error checking watchlist status:', err);
        }
    };

    const toggleWatchlist = async () => {
        if (!auth.user) {
            window.location.href = '/login';
            return;
        }

        setIsLoading(true);
        try {
            if (isInWatchlist) {
                // Remove from watchlist
                await axios.delete(`/api/watchlist/${product.id}`);
                setIsInWatchlist(false);
                setCurrentWatchlistCount(prev => Math.max(0, prev - 1));
            } else {
                // Add to watchlist
                await axios.post('/api/watchlist', { product_id: product.id });
                setIsInWatchlist(true);
                setCurrentWatchlistCount(prev => prev + 1);
            }
            setError(null);
        } catch (err) {
            console.error('Error toggling watchlist:', err);
            setError('Failed to update watchlist');
        } finally {
            setIsLoading(false);
        }
    };

    // Helper to preserve lang parameter
    const getUrlWithLang = (path) => {
        const currentLang = new URLSearchParams(window.location.search).get('lang');
        if (currentLang) {
            return `${path}?lang=${currentLang}`;
        }
        return path;
    };

    const getPriceChange = (index) => {
        if (index === 0) return '-';

        const current = history[index].price;
        const previous = history[index - 1].price;
        const diff = current - previous;

        if (diff === 0) return '→';
        if (diff > 0) return '+¥' + diff.toLocaleString();
        return '-¥' + Math.abs(diff).toLocaleString();
    };

    const getPriceChangeClass = (index) => {
        if (index === 0) return 'text-gray-300';

        const current = history[index].price;
        const previous = history[index - 1].price;

        if (current < previous) return 'text-green-600';
        if (current > previous) return 'text-uq-red';
        return 'text-gray-300';
    };

    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return `${date.getFullYear()}/${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}`;
    };

    const formatDateShort = (dateString) => {
        const date = new Date(dateString);
        return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}`;
    };

    const chartData = {
        labels: history.map(h => formatDateShort(h.date)),
        datasets: [
            {
                label: t.price,
                data: history.map(h => h.price),
                borderColor: '#000',
                backgroundColor: 'rgba(0, 0, 0, 0.05)',
                fill: true,
                tension: 0,
                pointRadius: 3,
                pointBackgroundColor: '#000',
                pointHoverRadius: 5,
                borderWidth: 1.5,
            },
        ],
    };

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: '#000',
                titleFont: { size: 11 },
                bodyFont: { size: 11 },
                padding: 8,
                displayColors: false,
                callbacks: {
                    label: function (context) {
                        return '¥' + context.raw.toLocaleString();
                    },
                },
            },
        },
        scales: {
            y: {
                beginAtZero: false,
                grid: {
                    color: 'rgba(0,0,0,0.05)',
                },
                ticks: {
                    font: { size: 10 },
                    color: '#999',
                    callback: function (value) {
                        return '¥' + value.toLocaleString();
                    },
                },
            },
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    font: { size: 10 },
                    color: '#999',
                    maxRotation: 0,
                },
            },
        },
    };

    const discountPercent = product.current_price < product.highest_price
        ? Math.round((1 - product.current_price / product.highest_price) * 100)
        : 0;

    const productUrl = product.brand === 'uniqlo'
        ? `https://www.uniqlo.com/jp/ja/products/${product.product_id}/${product.price_group}`
        : `https://www.gu-global.com/jp/ja/products/${product.product_id}/${product.price_group}`;

    return (
        <AppLayout title={`${product.name} - UPM`}>
            <div className="max-w-7xl mx-auto px-4 py-4">
                {/* Breadcrumb */}
                <nav className="mb-4">
                    <Link href={getUrlWithLang('/')} className="text-sm text-gray-500 hover:text-black transition-colors">
                        {t.back_to_list}
                    </Link>
                </nav>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {/* Product Image */}
                    <div className="bg-gray-50">
                        <div className="aspect-square relative">
                            <img
                                src={product.image_url || 'https://via.placeholder.com/600?text=No+Image'}
                                alt={product.name}
                                className="w-full h-full object-cover"
                            />
                            {product.current_price < product.highest_price && (
                                <div className="absolute top-0 left-0 bg-uq-red text-white px-3 py-1.5">
                                    <span className="text-xs font-bold">SALE</span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Product Info */}
                    <div className="py-2">
                        {/* Brand & Gender */}
                        <div className="flex items-center gap-3 mb-3">
                            <span className={`text-xs font-medium px-2 py-1 ${
                                product.brand === 'uniqlo' ? 'bg-uq-red text-white' : 'bg-gu-blue text-white'
                            }`}>
                                {product.brand.toUpperCase()}
                            </span>
                            {product.gender && (
                                <span className="text-xs text-gray-400">{product.gender}</span>
                            )}
                        </div>

                        {/* Product Name */}
                        <h1 className="text-xl font-medium text-gray-900 mb-6 leading-relaxed">{product.name}</h1>

                        {/* Price Section */}
                        <div className="mb-8">
                            <div className="flex items-baseline gap-3 mb-2">
                                <span className={`text-3xl font-bold ${
                                    product.current_price < product.highest_price ? 'text-uq-red' : 'text-gray-900'
                                }`}>
                                    ¥{product.current_price.toLocaleString()}
                                </span>
                                {product.current_price < product.highest_price && (
                                    <>
                                        <span className="text-sm text-gray-400 line-through">
                                            ¥{product.highest_price.toLocaleString()}
                                        </span>
                                        <span className="text-sm font-medium text-uq-red">
                                            -{discountPercent}%
                                        </span>
                                    </>
                                )}
                            </div>

                            {product.current_price <= product.lowest_price && (
                                <div className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-50 border border-yellow-200 text-yellow-800 text-xs">
                                    <svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                    </svg>
                                    <span className="font-medium">{t.lowest_now}</span>
                                </div>
                            )}
                        </div>

                        {/* Price Range */}
                        <div className="grid grid-cols-2 gap-4 mb-8">
                            <div className="p-4 bg-gray-50">
                                <p className="text-[10px] uppercase tracking-wider text-gray-400 mb-1">{t.lowest_price}</p>
                                <p className="text-lg font-bold text-gray-900">¥{product.lowest_price.toLocaleString()}</p>
                            </div>
                            <div className="p-4 bg-gray-50">
                                <p className="text-[10px] uppercase tracking-wider text-gray-400 mb-1">{t.highest_price}</p>
                                <p className="text-lg font-bold text-gray-900">¥{product.highest_price.toLocaleString()}</p>
                            </div>
                        </div>

                        {/* Product Details */}
                        <div className="border-t border-gray-200 pt-6 mb-8">
                            <dl className="space-y-3 text-sm">
                                <div className="flex">
                                    <dt className="w-32 text-gray-400">{t.product_id}</dt>
                                    <dd className="text-gray-900">{product.product_id}</dd>
                                </div>
                                <div className="flex">
                                    <dt className="w-32 text-gray-400">{t.price_group}</dt>
                                    <dd className="text-gray-900">{product.price_group}</dd>
                                </div>
                                <div className="flex">
                                    <dt className="w-32 text-gray-400">お気に入り</dt>
                                    <dd className="text-gray-900 font-medium">{currentWatchlistCount} users</dd>
                                </div>
                            </dl>
                        </div>

                        {/* Error Message */}
                        {error && (
                            <div className="mb-4 p-3 bg-red-100 text-red-700 text-sm rounded">
                                {error}
                            </div>
                        )}

                        {/* Action Buttons */}
                        <div className="space-y-3 mb-8">
                            <button
                                onClick={toggleWatchlist}
                                disabled={isLoading}
                                className={`w-full px-6 py-4 text-sm font-medium transition-colors ${
                                    isInWatchlist
                                        ? 'bg-red-600 hover:bg-red-700 text-white'
                                        : 'bg-blue-600 hover:bg-blue-700 text-white'
                                } disabled:opacity-50`}
                            >
                                {isLoading ? 'Processing...' : (isInWatchlist ? 'Remove from Watchlist' : 'Add to Watchlist')}
                            </button>

                            {auth.user && (
                                <Link
                                    href="/mypage"
                                    className="block text-center w-full px-6 py-4 bg-gray-200 hover:bg-gray-300 text-gray-900 text-sm font-medium transition-colors"
                                >
                                    View My Watchlist
                                </Link>
                            )}
                        </div>

                        {/* CTA Button */}
                        <a
                            href={productUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center justify-center w-full px-6 py-4 bg-black text-white text-sm font-medium hover:bg-gray-800 transition-colors"
                        >
                            {t.view_official}
                            <svg className="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>

                {/* Price History Section */}
                <div className="mt-12 border-t border-gray-200 pt-8">
                    <h2 className="text-lg font-medium text-gray-900 mb-6">{t.price_history}</h2>

                    {/* Chart */}
                    {history.length > 0 && (
                        <div className="bg-gray-50 p-4 mb-8">
                            <div className="h-64">
                                <Line data={chartData} options={chartOptions} />
                            </div>
                        </div>
                    )}

                    {history.length === 0 && (
                        <div className="text-center py-12 bg-gray-50">
                            <p className="text-sm text-gray-400">{t.no_history}</p>
                        </div>
                    )}

                    {/* History Table */}
                    {history.length > 0 && (
                        <div className="overflow-hidden">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200">
                                        <th className="text-left py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">{t.date}</th>
                                        <th className="text-right py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">{t.price}</th>
                                        <th className="text-right py-3 text-xs font-medium text-gray-400 uppercase tracking-wider">{t.change}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {[...history].reverse().map((item, index) => (
                                        <tr key={item.date}>
                                            <td className="py-3 text-sm text-gray-600">{formatDate(item.date)}</td>
                                            <td className="py-3 text-sm text-gray-900 text-right font-medium">¥{item.price.toLocaleString()}</td>
                                            <td className="py-3 text-sm text-right">
                                                <span className={getPriceChangeClass(history.length - index - 1)}>
                                                    {getPriceChange(history.length - index - 1)}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
