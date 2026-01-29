import { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';

export default function AppLayout({ children, title }) {
    const { locale, translations } = usePage().props;
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

        // Get current URL and update lang parameter
        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        
        // Use window.location to force full page reload with new lang parameter
        window.location.href = url.toString();
    };
    
    // Helper function to preserve lang parameter in links
    const getUrlWithLang = (path) => {
        const currentLang = new URLSearchParams(window.location.search).get('lang');
        if (currentLang) {
            return `${path}?lang=${currentLang}`;
        }
        return path;
    };

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-white flex flex-col">
                {/* Header */}
                <header className="sticky top-0 z-50 bg-white border-b border-gray-200">
                    <div className="max-w-7xl mx-auto">
                        <div className="flex items-center justify-between h-14 px-4">
                            {/* Logo & Tagline */}
                            <Link href={getUrlWithLang('/')} className="flex items-center gap-2 sm:gap-3">
                                <span className="text-xl sm:text-2xl font-bold tracking-tight">UPM</span>
                                <span className="text-xs sm:text-sm text-gray-500 border-l border-gray-300 pl-2 sm:pl-3">
                                    {translations.tagline}
                                </span>
                            </Link>

                            {/* Right Side */}
                            <div className="flex items-center gap-3">
                                {/* Language Switcher */}
                                <div className="relative">
                                    <button
                                        onClick={() => setLanguageOpen(!languageOpen)}
                                        className="flex items-center gap-1 px-2 py-1.5 text-sm text-gray-600 hover:text-black transition-colors"
                                    >
                                        <span className="font-medium">{locale.toUpperCase()}</span>
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>

                                    {/* Dropdown */}
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

                {/* Main Content */}
                <main className="flex-1">
                    {children}
                </main>

                {/* Footer */}
                <footer className="border-t border-gray-200 mt-auto">
                    <div className="max-w-7xl mx-auto py-6 px-4">
                        <p className="text-center text-xs text-gray-400">
                            {translations.footer_text}
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
