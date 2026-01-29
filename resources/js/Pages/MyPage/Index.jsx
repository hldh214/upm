import { useEffect, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import axios from 'axios';

interface Watchlist {
    id: number;
    user_id: number;
    product_id: number;
    created_at: string;
    updated_at: string;
    product: {
        id: number;
        name: string;
        brand: string;
        gender: string;
        current_price: number;
        image_url: string;
    };
    notificationSettings: any;
}

export default function MyPageIndex({ auth }: any) {
    const [watchlists, setWatchlists] = useState<Watchlist[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [activeTab, setActiveTab] = useState('watchlist');
    const { post } = useForm();

    useEffect(() => {
        loadWatchlists();
    }, []);

    const loadWatchlists = async () => {
        try {
            setLoading(true);
            const response = await axios.get('/api/watchlist');
            setWatchlists(response.data);
            setError(null);
        } catch (err) {
            setError('Failed to load watchlist');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleRemoveFromWatchlist = async (productId: number) => {
        try {
            await axios.delete(`/api/watchlist/${productId}`);
            setWatchlists(watchlists.filter(w => w.product_id !== productId));
        } catch (err) {
            setError('Failed to remove from watchlist');
            console.error(err);
        }
    };

    const handleLogout = () => {
        post('/logout');
    };

    return (
        <>
            <Head title="My Page" />
            <div className="min-h-screen bg-gray-100">
                <nav className="bg-white shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <div className="flex justify-between items-center">
                            <Link href="/" className="text-xl font-semibold">UPM</Link>
                            <div className="flex gap-4 items-center">
                                <span className="text-gray-700">Welcome, {auth.user?.name}</span>
                                <button
                                    onClick={handleLogout}
                                    className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                                >
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </nav>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="flex gap-4 border-b border-gray-200 mb-8">
                        <button
                            onClick={() => setActiveTab('watchlist')}
                            className={`px-4 py-2 font-medium ${
                                activeTab === 'watchlist'
                                    ? 'border-b-2 border-blue-600 text-blue-600'
                                    : 'text-gray-600 hover:text-gray-900'
                            }`}
                        >
                            Watchlist
                        </button>
                        <button
                            onClick={() => setActiveTab('notifications')}
                            className={`px-4 py-2 font-medium ${
                                activeTab === 'notifications'
                                    ? 'border-b-2 border-blue-600 text-blue-600'
                                    : 'text-gray-600 hover:text-gray-900'
                            }`}
                        >
                            Notifications
                        </button>
                    </div>

                    {activeTab === 'watchlist' && (
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 mb-8">My Watchlist</h1>

                            {error && (
                                <div className="mb-4 p-4 bg-red-100 text-red-700 rounded">
                                    {error}
                                </div>
                            )}

                            {loading ? (
                                <div className="text-center py-8">Loading...</div>
                            ) : watchlists.length === 0 ? (
                                <div className="text-center py-8 text-gray-600">
                                    <p>Your watchlist is empty</p>
                                    <Link href="/" className="text-blue-600 hover:text-blue-700 mt-4 inline-block">
                                        Browse products
                                    </Link>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    {watchlists.map((watchlist) => (
                                        <div key={watchlist.id} className="bg-white rounded-lg shadow">
                                            {watchlist.product.image_url && (
                                                <img
                                                    src={watchlist.product.image_url}
                                                    alt={watchlist.product.name}
                                                    className="w-full h-48 object-cover rounded-t-lg"
                                                />
                                            )}
                                            <div className="p-4">
                                                <h3 className="font-semibold text-lg text-gray-900 truncate">
                                                    {watchlist.product.name}
                                                </h3>
                                                <p className="text-sm text-gray-600">
                                                    {watchlist.product.brand} - {watchlist.product.gender}
                                                </p>
                                                <p className="text-xl font-bold text-gray-900 mt-2">
                                                    ¥{watchlist.product.current_price.toLocaleString()}
                                                </p>
                                                <div className="flex gap-2 mt-4">
                                                    <Link
                                                        href={`/products/${watchlist.product.id}`}
                                                        className="flex-1 text-center px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                                    >
                                                        View Details
                                                    </Link>
                                                    <button
                                                        onClick={() => handleRemoveFromWatchlist(watchlist.product_id)}
                                                        className="flex-1 px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                                                    >
                                                        Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'notifications' && (
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 mb-8">Notification Settings</h1>
                            <div className="bg-white rounded-lg shadow p-6">
                                <p className="text-gray-600">Configure notification settings for your watchlist items in the product details page.</p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
