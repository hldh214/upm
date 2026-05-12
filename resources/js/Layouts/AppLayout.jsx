import { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';

export default function AppLayout({ children, title }) {
    const { auth, locale, translations } = usePage().props;
    const [languageOpen, setLanguageOpen] = useState(false);

    const languages = {
        en: 'English',
        ja: '日本語',
    };

    const setLanguage = (lang) => {
        if (locale === lang) {
            setLanguageOpen(false);
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);

        window.location.href = url.toString();
    };

    const getUrlWithLang = (path) => {
        const currentLang = new URLSearchParams(window.location.search).get('lang');
        if (currentLang) {
            return `${path}?lang=${currentLang}`;
        }
        return path;
    };

    const logout = () => {
        router.post('/logout');
    };

    const authLinks = auth?.user ? (
        <div className="flex w-full items-center gap-2 sm:w-auto">
            <Link
                href={getUrlWithLang('/mypage')}
                className="inline-flex flex-1 items-center justify-center border border-gray-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-800 transition-colors hover:border-black hover:text-black sm:flex-none sm:px-4"
            >
                {translations.my_page || 'My Page'}
            </Link>
            <button
                type="button"
                onClick={logout}
                className="inline-flex flex-1 items-center justify-center border border-gray-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-800 transition-colors hover:border-black hover:text-black sm:flex-none sm:border-0 sm:px-0 sm:text-gray-500 sm:underline-offset-4 sm:hover:underline"
            >
                {translations.logout || 'Logout'}
            </button>
        </div>
    ) : (
        <div className="flex w-full items-center gap-2 sm:w-auto">
            <Link
                href={getUrlWithLang('/login')}
                className="inline-flex flex-1 items-center justify-center border border-gray-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-800 transition-colors hover:border-black hover:text-black sm:flex-none sm:px-4"
            >
                {translations.sign_in}
            </Link>
            <Link
                href={getUrlWithLang('/register')}
                className="inline-flex flex-1 items-center justify-center bg-black px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition-colors hover:bg-gray-800 sm:flex-none sm:px-4"
            >
                {translations.sign_up}
            </Link>
        </div>
    );

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-white flex flex-col pb-16 sm:pb-0">
                <header className="sticky top-0 z-50 bg-white border-b border-gray-200">
                    <div className="max-w-7xl mx-auto">
                        <div className="flex h-16 items-center justify-between gap-3 px-4">
                            <Link href={getUrlWithLang('/')} className="flex min-w-0 items-center gap-2 sm:gap-3">
                                <span className="text-xl sm:text-2xl font-bold tracking-tight">UPM</span>
                                <span className="hidden truncate border-l border-gray-300 pl-2 text-xs text-gray-500 sm:block sm:pl-3 sm:text-sm">
                                    {translations.tagline}
                                </span>
                            </Link>

                            <div className="flex items-center gap-2 sm:gap-4">
                                <div className="hidden sm:block">
                                    {authLinks}
                                </div>

                                <div className="relative">
                                    <button
                                        type="button"
                                        onClick={() => setLanguageOpen(!languageOpen)}
                                        className="flex items-center gap-1 px-2 py-2 text-sm text-gray-600 hover:text-black transition-colors"
                                    >
                                        <span className="font-medium">{locale.toUpperCase()}</span>
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>

                                    {languageOpen && (
                                        <>
                                            <div
                                                className="fixed inset-0 z-40"
                                                onClick={() => setLanguageOpen(false)}
                                            ></div>
                                            <div className="absolute right-0 mt-1 w-32 bg-white border border-gray-200 shadow-lg z-50">
                                                {Object.entries(languages).map(([code, name]) => (
                                                    <button
                                                        key={code}
                                                        type="button"
                                                        onClick={() => setLanguage(code)}
                                                        className={`w-full px-4 py-2.5 text-sm text-left text-gray-700 ${
                                                            locale === code ? 'bg-gray-50 font-medium' : 'hover:bg-gray-50'
                                                        }`}
                                                    >
                                                        {name}
                                                    </button>
                                                ))}
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main className="flex-1">
                    {children}
                </main>

                <footer className="border-t border-gray-200 mt-auto">
                    <div className="max-w-7xl mx-auto py-6 px-4">
                        <p className="text-center text-xs text-gray-400">
                            {translations.footer_text}
                        </p>
                    </div>
                </footer>

                <div className="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white/95 px-4 py-3 shadow-[0_-8px_24px_rgba(0,0,0,0.08)] backdrop-blur sm:hidden">
                    {authLinks}
                </div>
            </div>
        </>
    );
}
