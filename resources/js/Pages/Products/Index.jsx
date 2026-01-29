import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Index({ products: initialProducts, stats, filters: initialFilters }) {
    const { translations } = usePage().props;
    const t = translations;

    // Use props directly from Inertia
    const products = initialProducts.data;
    const pagination = {
        current_page: initialProducts.current_page,
        last_page: initialProducts.last_page,
        total: initialProducts.total,
    };

    // Parse filters from props - use URL params as source of truth
    const urlParams = new URLSearchParams(window.location.search);
    const priceChangeParam = urlParams.get('price_change');

    const filters = {
        q: initialFilters.q || '',
        brand: initialFilters.brand || '',
        gender: initialFilters.gender || '',
        sort: initialFilters.sort || '',
        changeDays: parseInt(urlParams.get('change_days')) || parseInt(initialFilters.change_days) || 3,
        priceDropped: priceChangeParam ? priceChangeParam.includes('dropped') : true,
        priceRaised: priceChangeParam ? priceChangeParam.includes('raised') : false,
    };

    // Helper to preserve lang parameter in product links
    const getProductUrl = (productId) => {
        const currentLang = new URLSearchParams(window.location.search).get('lang');
        if (currentLang) {
            return `/products/${productId}?lang=${currentLang}`;
        }
        return `/products/${productId}`;
    };

    const updateFilters = (newFilters) => {
        const params = { ...newFilters };

        // Build price_change param
        const priceChanges = [];
        if (newFilters.priceDropped) priceChanges.push('dropped');
        if (newFilters.priceRaised) priceChanges.push('raised');
        if (priceChanges.length > 0) {
            params.price_change = priceChanges.join(',');
            params.change_days = newFilters.changeDays;
        }

        // Remove internal filter states
        delete params.priceDropped;
        delete params.priceRaised;
        delete params.changeDays;

        // If we have price change filters, ensure change_days is included
        if (priceChanges.length > 0 && !params.change_days) {
            params.change_days = newFilters.changeDays || 3;
        }

        // Remove empty params
        Object.keys(params).forEach(key => {
            if (!params[key] && params[key] !== 0) delete params[key];
        });

        // Use Inertia router to navigate
        router.get('/', params, {
            preserveState: true,
            preserveScroll: false,
            only: ['products', 'filters'],
        });
    };

    const setQuickFilter = (type) => {
        let newDropped, newRaised;

        if (type === 'dropped') {
            newDropped = true;
            newRaised = false;
        } else if (type === 'raised') {
            newDropped = false;
            newRaised = true;
        } else {
            newDropped = false;
            newRaised = false;
        }

        // Check if already in this state
        if (filters.priceDropped === newDropped && filters.priceRaised === newRaised) {
            return;
        }

        updateFilters({
            ...filters,
            priceDropped: newDropped,
            priceRaised: newRaised,
        });
    };

    const updateFilter = (key, value) => {
        updateFilters({ ...filters, [key]: value });
    };

    const goToPage = (page) => {
        if (page < 1 || page > pagination.last_page) return;

        const params = {
            ...filters,
            page,
        };

        // Build price_change param
        const priceChanges = [];
        if (filters.priceDropped) priceChanges.push('dropped');
        if (filters.priceRaised) priceChanges.push('raised');
        if (priceChanges.length > 0) {
            params.price_change = priceChanges.join(',');
            params.change_days = filters.changeDays;
        }

        // Remove internal filter states
        delete params.priceDropped;
        delete params.priceRaised;
        delete params.changeDays;

        router.get('/', params, {
            preserveState: true,
            preserveScroll: false,
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const getPageNumbers = () => {
        const current = pagination.current_page;
        const last = pagination.last_page;
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
    };

    return (
        <AppLayout title={t.tagline}>
            <div className="max-w-7xl mx-auto px-4 py-4">
                {/* Category Tabs */}
                <div className="border-b border-gray-200 mb-6">
                    <nav className="flex gap-6 -mb-px overflow-x-auto hide-scrollbar">
                        <button
                            onClick={() => setQuickFilter('dropped')}
                            className={`pb-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors ${
                                filters.priceDropped && !filters.priceRaised
                                    ? 'border-black text-black'
                                    : 'border-transparent text-gray-500 hover:text-black'
                            }`}
                        >
                            {t.tab_price_drops}
                        </button>
                        <button
                            onClick={() => setQuickFilter('all')}
                            className={`pb-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors ${
                                !filters.priceDropped && !filters.priceRaised
                                    ? 'border-black text-black'
                                    : 'border-transparent text-gray-500 hover:text-black'
                            }`}
                        >
                            {t.tab_all_products}
                        </button>
                        <button
                            onClick={() => setQuickFilter('raised')}
                            className={`pb-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors ${
                                filters.priceRaised && !filters.priceDropped
                                    ? 'border-black text-black'
                                    : 'border-transparent text-gray-500 hover:text-black'
                            }`}
                        >
                            {t.tab_price_rises}
                        </button>
                    </nav>
                </div>

                {/* Time Range */}
                {(filters.priceDropped || filters.priceRaised) && (
                    <div className="mb-6">
                        <div className="flex flex-wrap gap-2">
                            {[1, 3, 7, 14, 30].map(day => (
                                <button
                                    key={day}
                                    onClick={() => updateFilter('changeDays', day)}
                                    className={`px-4 py-1.5 text-xs font-medium transition-colors ${
                                        filters.changeDays === day
                                            ? 'bg-black text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    {day}{day === 1 ? t.day : t.days}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                {/* Filters Row */}
                <div className="flex flex-col sm:flex-row gap-3 mb-6">
                    {/* Search */}
                    <div className="flex-1">
                        <div className="relative">
                            <input
                                type="text"
                                defaultValue={filters.q}
                                onKeyUp={(e) => {
                                    if (e.key === 'Enter') {
                                        updateFilter('q', e.target.value);
                                    }
                                }}
                                placeholder={t.search_placeholder}
                                className="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none"
                            />
                            <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    {/* Filter Dropdowns */}
                    <div className="flex gap-2">
                        <select
                            value={filters.brand}
                            onChange={(e) => updateFilter('brand', e.target.value)}
                            className="px-3 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none bg-white min-w-[100px]"
                        >
                            <option value="">{t.all_brands}</option>
                            <option value="uniqlo">UNIQLO</option>
                            <option value="gu">GU</option>
                        </select>

                        <select
                            value={filters.gender}
                            onChange={(e) => updateFilter('gender', e.target.value)}
                            className="px-3 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none bg-white min-w-[100px]"
                        >
                            <option value="">{t.all_genders}</option>
                            <option value="MEN">MEN</option>
                            <option value="WOMEN">WOMEN</option>
                            <option value="KIDS">KIDS</option>
                            <option value="BABY">BABY</option>
                        </select>

                        <select
                            value={filters.sort}
                            onChange={(e) => updateFilter('sort', e.target.value)}
                            className="px-3 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none bg-white min-w-[120px]"
                        >
                            <option value="">{t.sort_latest}</option>
                            <option value="price_asc">{t.sort_price_low}</option>
                            <option value="price_desc">{t.sort_price_high}</option>
                            {(filters.priceDropped || filters.priceRaised) && (
                                <option value="drop_percent">{t.sort_change_percent}</option>
                            )}
                        </select>
                    </div>
                </div>

                {/* Results Count */}
                <div className="flex items-center justify-between mb-4">
                    <p className="text-sm text-gray-500">
                        {pagination.total.toLocaleString()} {t.items}
                    </p>
                </div>

                {/* Product Grid */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-px bg-gray-200">
                    {products.map(product => (
                        <Link key={product.id} href={getProductUrl(product.id)} className="bg-white block group">
                            {/* Image Container */}
                            <div className="relative aspect-square bg-gray-50 overflow-hidden">
                                <img
                                    src={product.image_url || 'https://via.placeholder.com/400?text=No+Image'}
                                    alt={product.name}
                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                />

                                {/* Sale Badge */}
                                {product.price_change_type === 'dropped' && (
                                    <div className="absolute top-0 left-0 bg-uq-red text-white px-2 py-1">
                                        <span className="text-xs font-bold">SALE</span>
                                    </div>
                                )}

                                {/* Brand indicator */}
                                <div className="absolute top-2 right-2">
                                    <span className={`inline-block w-2 h-2 rounded-full ${
                                        product.brand === 'uniqlo' ? 'bg-uq-red' : 'bg-gu-blue'
                                    }`}></span>
                                </div>
                            </div>

                            {/* Product Info */}
                            <div className="p-3">
                                {/* Brand & Gender */}
                                <div className="flex items-center gap-2 mb-1">
                                    <span className="text-[10px] font-medium text-gray-400 uppercase">{product.brand}</span>
                                    {product.gender && (
                                        <>
                                            <span className="text-[10px] text-gray-300">|</span>
                                            <span className="text-[10px] text-gray-400">{product.gender}</span>
                                        </>
                                    )}
                                </div>

                                {/* Product Name */}
                                <h3 className="text-xs text-gray-900 line-clamp-2 mb-2 leading-relaxed">{product.name}</h3>

                                {/* Price Section */}
                                <div className="space-y-0.5">
                                    {product.price_change_type === 'dropped' ? (
                                        <div>
                                            <div className="flex items-baseline gap-2">
                                                <span className="text-sm font-bold text-uq-red">¥{product.current_price.toLocaleString()}</span>
                                                <span className="text-xs text-gray-400 line-through">¥{product.previous_price.toLocaleString()}</span>
                                            </div>
                                            <div className="text-[10px] text-uq-red font-medium">
                                                <span>-{product.price_change_percent}%</span>
                                                <span className="text-gray-400 ml-1">(-¥{product.price_change_amount.toLocaleString()})</span>
                                            </div>
                                        </div>
                                    ) : product.price_change_type === 'raised' ? (
                                        <div>
                                            <div className="flex items-baseline gap-2">
                                                <span className="text-sm font-bold text-gray-900">¥{product.current_price.toLocaleString()}</span>
                                                <span className="text-xs text-gray-400 line-through">¥{product.previous_price.toLocaleString()}</span>
                                            </div>
                                            <div className="text-[10px] text-gray-500">
                                                <span>+{product.price_change_percent}%</span>
                                            </div>
                                        </div>
                                    ) : (
                                        <div>
                                            <span className="text-sm font-bold text-gray-900">¥{product.current_price.toLocaleString()}</span>
                                            {product.current_price < product.highest_price && (
                                                <div className="text-[10px] text-gray-400">
                                                    {t.highest}: ¥{product.highest_price.toLocaleString()}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>

                {/* Empty State */}
                {products.length === 0 && (
                    <div className="text-center py-20">
                        <p className="text-gray-400 text-sm">{t.no_products}</p>
                    </div>
                )}

                {/* Pagination */}
                {pagination.last_page > 1 && (
                    <div className="mt-8 flex justify-center">
                        <nav className="flex items-center gap-1">
                            {/* Previous */}
                            <button
                                onClick={() => goToPage(pagination.current_page - 1)}
                                disabled={pagination.current_page === 1}
                                className="w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            {/* Page Numbers */}
                            {getPageNumbers().map((item, index) => (
                                <button
                                    key={`page-${index}`}
                                    onClick={() => item !== '...' && goToPage(item)}
                                    disabled={item === '...'}
                                    className={`w-10 h-10 flex items-center justify-center text-sm transition-colors ${
                                        item === pagination.current_page
                                            ? 'bg-black text-white'
                                            : item !== '...'
                                            ? 'hover:bg-gray-100'
                                            : 'cursor-default'
                                    }`}
                                >
                                    {item}
                                </button>
                            ))}

                            {/* Next */}
                            <button
                                onClick={() => goToPage(pagination.current_page + 1)}
                                disabled={pagination.current_page === pagination.last_page}
                                className="w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                            >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </nav>
                    </div>
                )}

                {/* Page indicator */}
                {pagination.last_page > 1 && (
                    <div className="mt-3 text-center">
                        <span className="text-xs text-gray-400">
                            {pagination.current_page} {t.of} {pagination.last_page}
                        </span>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
