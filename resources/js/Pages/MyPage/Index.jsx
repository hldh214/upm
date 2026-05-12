import { useEffect, useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout';

const brands = ['uniqlo', 'gu'];
const genders = ['MEN', 'WOMEN', 'KIDS', 'BABY', 'UNISEX'];

export default function MyPageIndex({ auth }) {
    const [watchlists, setWatchlists] = useState([]);
    const [newProductSettings, setNewProductSettings] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [activeTab, setActiveTab] = useState('watchlist');
    const { post } = useForm();

    useEffect(() => {
        loadMyPage();
    }, []);

    const loadMyPage = async () => {
        try {
            setLoading(true);
            const [watchlistResponse, newProductResponse] = await Promise.all([
                axios.get('/api/watchlist'),
                axios.get('/api/notifications/new-product'),
            ]);

            setWatchlists(watchlistResponse.data);
            setNewProductSettings(newProductResponse.data.settings || []);
            setError(null);
        } catch (err) {
            setError('Failed to load my page');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleRemoveFromWatchlist = async (productId) => {
        try {
            await axios.delete(`/api/watchlist/${productId}`);
            setWatchlists(watchlists.filter((watchlist) => watchlist.product_id !== productId));
        } catch (err) {
            setError('Failed to remove from watchlist');
            console.error(err);
        }
    };

    const watchlistSettingsPayload = (settings) => ({
        price_drop_enabled: Boolean(settings.price_drop_enabled),
        price_drop_target: settings.price_drop_target || '',
        price_change_enabled: Boolean(settings.price_change_enabled),
        price_change_min_amount: settings.price_change_min_amount || '',
    });

    const setWatchlistSettingLocal = (watchlistId, field, value) => {
        setWatchlists((items) => items.map((item) => (
            item.id === watchlistId
                ? {
                    ...item,
                    notification_settings: {
                        ...(item.notification_settings || {}),
                        [field]: value,
                    },
                }
                : item
        )));
    };

    const saveWatchlistSettings = async (watchlistId, settings) => {
        try {
            const response = await axios.put(`/api/notifications/settings/${watchlistId}`, watchlistSettingsPayload(settings));
            setWatchlists((items) => items.map((item) => (
                item.id === watchlistId
                    ? { ...item, notification_settings: response.data }
                    : item
            )));
        } catch (err) {
            setError('Failed to update notification settings');
            console.error(err);
        }
    };

    const updateWatchlistSetting = async (watchlist, field, value) => {
        const current = watchlist.notification_settings || {};
        const nextSettings = {
            ...watchlistSettingsPayload(current),
            [field]: value,
        };

        setWatchlistSettingLocal(watchlist.id, field, value);
        await saveWatchlistSettings(watchlist.id, nextSettings);
    };

    const findNewProductSetting = (brand, gender) => newProductSettings.find((setting) => (
        setting.brand === brand && setting.gender === gender
    ));

    const updateNewProductSetting = async (brand, gender, enabled) => {
        const current = findNewProductSetting(brand, gender);
        const optimistic = current
            ? newProductSettings.map((setting) => (
                setting.id === current.id ? { ...setting, enabled } : setting
            ))
            : [...newProductSettings, { brand, gender, enabled }];

        setNewProductSettings(optimistic);

        try {
            const response = await axios.put('/api/notifications/new-product', { brand, gender, enabled });
            setNewProductSettings((settings) => {
                const withoutCurrent = settings.filter((setting) => !(
                    setting.brand === brand && setting.gender === gender
                ));

                return [...withoutCurrent, response.data];
            });
        } catch (err) {
            setError('Failed to update new product notification settings');
            console.error(err);
        }
    };

    const handleLogout = () => {
        post('/logout');
    };

    const globalNewProductEnabled = Boolean(findNewProductSetting('*', '*')?.enabled);

    return (
        <AppLayout title="My Page">
            <div className="max-w-7xl mx-auto px-4 py-8">
                <div className="flex flex-col gap-4 border-b border-gray-200 pb-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">My Page</h1>
                        <p className="mt-1 text-sm text-gray-500">{auth.user?.email}</p>
                    </div>
                    <button
                        onClick={handleLogout}
                        className="self-start border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:border-black hover:text-black sm:self-auto"
                    >
                        Logout
                    </button>
                </div>

                <div className="mt-6 flex gap-6 border-b border-gray-200">
                    <button
                        onClick={() => setActiveTab('watchlist')}
                        className={`pb-3 text-sm font-medium ${activeTab === 'watchlist' ? 'border-b-2 border-black text-black' : 'text-gray-500 hover:text-black'}`}
                    >
                        Watchlist
                    </button>
                    <button
                        onClick={() => setActiveTab('notifications')}
                        className={`pb-3 text-sm font-medium ${activeTab === 'notifications' ? 'border-b-2 border-black text-black' : 'text-gray-500 hover:text-black'}`}
                    >
                        New Product Notifications
                    </button>
                </div>

                {error && (
                    <div className="mt-6 border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        {error}
                    </div>
                )}

                {loading ? (
                    <div className="py-12 text-center text-sm text-gray-500">Loading...</div>
                ) : activeTab === 'watchlist' ? (
                    <div className="mt-8">
                        {watchlists.length === 0 ? (
                            <div className="border border-gray-200 bg-gray-50 py-12 text-center">
                                <p className="text-sm text-gray-600">Your watchlist is empty</p>
                                <Link href="/" className="mt-4 inline-block text-sm font-medium text-black underline">
                                    Browse products
                                </Link>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                {watchlists.map((watchlist) => {
                                    const settings = watchlist.notification_settings || {};

                                    return (
                                        <div key={watchlist.id} className="border border-gray-200 bg-white">
                                            <div className="flex gap-4 p-4">
                                                <img
                                                    src={watchlist.product.image_url || 'https://via.placeholder.com/160?text=No+Image'}
                                                    alt={watchlist.product.name}
                                                    className="h-28 w-28 flex-none object-cover"
                                                />
                                                <div className="min-w-0 flex-1">
                                                    <h2 className="truncate text-sm font-semibold text-gray-900">{watchlist.product.name}</h2>
                                                    <p className="mt-1 text-xs uppercase text-gray-500">{watchlist.product.brand} / {watchlist.product.gender}</p>
                                                    <p className="mt-2 text-lg font-bold text-gray-900">¥{watchlist.product.current_price.toLocaleString()}</p>
                                                    <div className="mt-3 flex gap-3 text-xs">
                                                        <Link href={`/products/${watchlist.product.id}`} className="font-medium text-black underline">
                                                            Details
                                                        </Link>
                                                        <button onClick={() => handleRemoveFromWatchlist(watchlist.product_id)} className="text-red-600 underline">
                                                            Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="border-t border-gray-200 p-4">
                                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                    <label className="flex items-center gap-2 text-sm text-gray-700">
                                                        <input
                                                            type="checkbox"
                                                            checked={Boolean(settings.price_drop_enabled)}
                                                            onChange={(e) => updateWatchlistSetting(watchlist, 'price_drop_enabled', e.currentTarget.checked)}
                                                            className="h-4 w-4 border-gray-300 text-black focus:ring-0"
                                                        />
                                                        値下がり通知
                                                    </label>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        placeholder="最安価格 xxx 以下"
                                                        value={settings.price_drop_target || ''}
                                                        onChange={(e) => setWatchlistSettingLocal(watchlist.id, 'price_drop_target', e.currentTarget.value)}
                                                        onBlur={(e) => saveWatchlistSettings(watchlist.id, {
                                                            ...settings,
                                                            price_drop_target: e.currentTarget.value,
                                                        })}
                                                        className="border border-gray-300 px-3 py-2 text-sm focus:border-black focus:outline-none focus:ring-0"
                                                    />
                                                    <label className="flex items-center gap-2 text-sm text-gray-700">
                                                        <input
                                                            type="checkbox"
                                                            checked={Boolean(settings.price_change_enabled)}
                                                            onChange={(e) => updateWatchlistSetting(watchlist, 'price_change_enabled', e.currentTarget.checked)}
                                                            className="h-4 w-4 border-gray-300 text-black focus:ring-0"
                                                        />
                                                        最安変動通知
                                                    </label>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        placeholder="変動額 xxx 以上"
                                                        value={settings.price_change_min_amount || ''}
                                                        onChange={(e) => setWatchlistSettingLocal(watchlist.id, 'price_change_min_amount', e.currentTarget.value)}
                                                        onBlur={(e) => saveWatchlistSettings(watchlist.id, {
                                                            ...settings,
                                                            price_change_min_amount: e.currentTarget.value,
                                                        })}
                                                        className="border border-gray-300 px-3 py-2 text-sm focus:border-black focus:outline-none focus:ring-0"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="mt-8 space-y-6">
                        <div className="border border-gray-200 bg-white p-5">
                            <label className="flex items-center justify-between gap-4">
                                <span>
                                    <span className="block text-sm font-semibold text-gray-900">新商品通知 総スイッチ</span>
                                    <span className="mt-1 block text-xs text-gray-500">Enabled categories notify only when this switch is also on.</span>
                                </span>
                                <input
                                    type="checkbox"
                                    checked={globalNewProductEnabled}
                                    onChange={(e) => updateNewProductSetting('*', '*', e.currentTarget.checked)}
                                    className="h-5 w-5 border-gray-300 text-black focus:ring-0"
                                />
                            </label>
                        </div>

                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            {brands.map((brand) => (
                                <div key={brand} className="border border-gray-200 bg-white p-5">
                                    <h2 className="text-sm font-bold uppercase text-gray-900">{brand}</h2>
                                    <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        {genders.map((gender) => {
                                            const setting = findNewProductSetting(brand, gender);

                                            return (
                                                <label key={`${brand}-${gender}`} className="flex items-center justify-between border border-gray-100 p-3 text-sm">
                                                    <span className="text-gray-700">{gender}</span>
                                                    <input
                                                        type="checkbox"
                                                        checked={Boolean(setting?.enabled)}
                                                        onChange={(e) => updateNewProductSetting(brand, gender, e.currentTarget.checked)}
                                                        className="h-4 w-4 border-gray-300 text-black focus:ring-0"
                                                    />
                                                </label>
                                            );
                                        })}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
