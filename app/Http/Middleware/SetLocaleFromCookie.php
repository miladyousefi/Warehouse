<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = ['en', 'tr'];
        $defaultLocale = config('app.locale', 'tr');

        // Try to get locale from:
        // 1. Session (if user is authenticated)
        // 2. Cookie
        // 3. Default config
        $locale = null;

        if ($request->user()) {
            $locale = session('locale');
        }

        if (!$locale) {
            $locale = $request->cookie('locale');
        }

        // Validate and fallback
        if (!$locale || !in_array($locale, $availableLocales, true)) {
            $locale = $defaultLocale;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
