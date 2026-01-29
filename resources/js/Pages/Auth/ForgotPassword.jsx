import { useForm } from '@inertiajs/react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function ForgotPassword() {
    const { translations } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <AppLayout title={translations?.forgot_password_title || 'Reset Password'}>
            <div className="max-w-7xl mx-auto px-4 py-8 sm:py-12">
                <div className="max-w-md mx-auto">
                    {/* Card Container */}
                    <div className="border border-gray-200 bg-white">
                        {/* Header */}
                        <div className="border-b border-gray-200 px-6 py-5">
                            <h1 className="text-xl font-bold text-gray-900">{translations?.forgot_password_title || 'Reset Password'}</h1>
                            <p className="text-xs text-gray-500 mt-1">{translations?.forgot_password_subtitle || "Enter your email address and we'll send you a link to reset your password."}</p>
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

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-3 px-4 text-sm font-medium text-white bg-black hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? (translations?.sending || 'Sending...') : (translations?.send_reset_link || 'Send Reset Link')}
                            </button>
                        </form>

                        {/* Footer */}
                        <div className="border-t border-gray-200 px-6 py-4 bg-gray-50">
                            <p className="text-center text-xs">
                                <Link href="/login" className="text-gray-600 hover:text-black transition-colors underline">
                                    {translations?.back_to_login || 'Back to sign in'}
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
