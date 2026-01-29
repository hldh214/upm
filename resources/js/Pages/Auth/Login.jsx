import { useForm } from '@inertiajs/react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Login() {
    const { translations } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <AppLayout title={translations?.login_title || 'Login'}>
            <div className="max-w-7xl mx-auto px-4 py-8 sm:py-12">
                <div className="max-w-md mx-auto">
                    {/* Card Container */}
                    <div className="border border-gray-200 bg-white">
                        {/* Header */}
                        <div className="border-b border-gray-200 px-6 py-5">
                            <h1 className="text-xl font-bold text-gray-900">{translations?.login_title || 'Sign In'}</h1>
                            <p className="text-xs text-gray-500 mt-1">{translations?.login_subtitle || 'Sign in to your account to continue'}</p>
                        </div>

                        {/* Form */}
                        <form className="px-6 py-6 space-y-5" onSubmit={submit}>
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
                                    autoComplete="current-password"
                                    required
                                    className="w-full px-3 py-2.5 text-sm border border-gray-300 focus:border-black focus:ring-0 focus:outline-none transition-colors"
                                    placeholder={translations?.password_placeholder || 'Enter your password'}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.currentTarget.value)}
                                />
                                {errors.password && (
                                    <p className="text-xs text-red-600 mt-1.5">{errors.password}</p>
                                )}
                            </div>

                            <div className="flex items-center justify-between pt-1">
                                <label className="flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        className="w-4 h-4 border border-gray-300 text-black focus:ring-0 focus:ring-offset-0"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.currentTarget.checked)}
                                    />
                                    <span className="ml-2 text-xs text-gray-600">{translations?.remember_me || 'Remember me'}</span>
                                </label>

                                <Link
                                    href="/forgot-password"
                                    className="text-xs text-gray-600 hover:text-black transition-colors underline"
                                >
                                    {translations?.forgot_password || 'Forgot password?'}
                                </Link>
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-3 px-4 text-sm font-medium text-white bg-black hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? (translations?.signing_in || 'Signing in...') : (translations?.sign_in || 'Sign In')}
                            </button>
                        </form>

                        {/* Footer */}
                        <div className="border-t border-gray-200 px-6 py-4 bg-gray-50">
                            <p className="text-center text-xs text-gray-600">
                                {translations?.no_account || "Don't have an account?"}{' '}
                                <Link href="/register" className="text-black font-medium hover:underline">
                                    {translations?.sign_up || 'Sign up'}
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
