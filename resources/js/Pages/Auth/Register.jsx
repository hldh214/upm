import { useForm } from '@inertiajs/react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Register() {
    const { translations } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/register');
    };

    return (
        <AppLayout title={translations?.register_title || 'Register'}>
            <div className="max-w-7xl mx-auto px-4 py-8 sm:py-12">
                <div className="max-w-md mx-auto">
                    {/* Card Container */}
                    <div className="border border-gray-200 bg-white">
                        {/* Header */}
                        <div className="border-b border-gray-200 px-6 py-5">
                            <h1 className="text-xl font-bold text-gray-900">{translations?.register_title || 'Create Account'}</h1>
                            <p className="text-xs text-gray-500 mt-1">{translations?.register_subtitle || 'Sign up to get started'}</p>
                        </div>

                        {/* Form */}
                        <form className="px-6 py-6 space-y-5" onSubmit={submit}>
                            <div>
                                <label htmlFor="name" className="block text-xs font-medium text-gray-700 mb-2 uppercase tracking-wide">
                                    {translations?.name || 'Name'}
                                </label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    autoComplete="name"
                                    required
                                    className="w-full px-3 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none transition-colors"
                                    placeholder={translations?.name_placeholder || 'Your full name'}
                                    value={data.name}
                                    onChange={(e) => setData('name', e.currentTarget.value)}
                                />
                                {errors.name && (
                                    <p className="text-xs text-red-600 mt-1.5">{errors.name}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="email" className="block text-xs font-medium text-gray-700 mb-2 uppercase tracking-wide">
                                    {translations?.email || 'Email'}
                                </label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    required
                                    className="w-full px-3 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none transition-colors"
                                    placeholder={translations?.email_placeholder || 'name@example.com'}
                                    value={data.email}
                                    onChange={(e) => setData('email', e.currentTarget.value)}
                                />
                                {errors.email && (
                                    <p className="text-xs text-red-600 mt-1.5">{errors.email}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="password" className="block text-xs font-medium text-gray-700 mb-2 uppercase tracking-wide">
                                    {translations?.password || 'Password'}
                                </label>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="new-password"
                                    required
                                    className="w-full px-3 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none transition-colors"
                                    placeholder={translations?.password_placeholder || 'Enter a password'}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.currentTarget.value)}
                                />
                                {errors.password && (
                                    <p className="text-xs text-red-600 mt-1.5">{errors.password}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="password_confirmation" className="block text-xs font-medium text-gray-700 mb-2 uppercase tracking-wide">
                                    {translations?.confirm_password || 'Confirm Password'}
                                </label>
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autoComplete="new-password"
                                    required
                                    className="w-full px-3 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none transition-colors"
                                    placeholder={translations?.confirm_password_placeholder || 'Confirm your password'}
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.currentTarget.value)}
                                />
                                {errors.password_confirmation && (
                                    <p className="text-xs text-red-600 mt-1.5">{errors.password_confirmation}</p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-3 px-4 text-sm font-medium text-white bg-black hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? (translations?.signing_up || 'Creating account...') : (translations?.sign_up || 'Sign Up')}
                            </button>
                        </form>

                        {/* Footer */}
                        <div className="border-t border-gray-200 px-6 py-4 bg-gray-50">
                            <p className="text-center text-xs text-gray-600">
                                {translations?.have_account || 'Already have an account?'}{' '}
                                <Link href="/login" className="text-black font-medium hover:underline">
                                    {translations?.sign_in || 'Sign in'}
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
