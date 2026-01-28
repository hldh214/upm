<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales
     */
    protected array $supportedLocales = ['en', 'ja'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Determine the locale from various sources
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Check cookie (user's explicit choice)
        $cookieLocale = $request->cookie('upm_language');
        if ($cookieLocale && in_array($cookieLocale, $this->supportedLocales)) {
            return $cookieLocale;
        }

        // 2. Check Accept-Language header
        $acceptLanguage = $request->header('Accept-Language', '');
        $locale = $this->parseAcceptLanguage($acceptLanguage);

        if ($locale) {
            return $locale;
        }

        // 3. Default to English
        return 'en';
    }

    /**
     * Parse Accept-Language header and return best matching locale
     */
    protected function parseAcceptLanguage(string $header): ?string
    {
        if (empty($header)) {
            return null;
        }

        // Parse language preferences with quality values
        $languages = [];
        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            // Parse quality value (e.g., "ja;q=0.9")
            if (preg_match('/^([a-z]{2,3})(?:-[A-Za-z]{2,4})?(?:;q=([0-9.]+))?$/i', $part, $matches)) {
                $lang = strtolower($matches[1]);
                $quality = isset($matches[2]) ? (float) $matches[2] : 1.0;
                $languages[$lang] = $quality;
            }
        }

        // Sort by quality (highest first)
        arsort($languages);

        // Find first matching supported locale
        foreach (array_keys($languages) as $lang) {
            if (in_array($lang, $this->supportedLocales)) {
                return $lang;
            }
        }

        return null;
    }
}
