<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang') ?? $request->session()->get('locale');

        if (is_string($locale) && in_array($locale, ['fr', 'en'], true)) {
            app()->setLocale($locale);

            if ($request->query('lang')) {
                $request->session()->put('locale', $locale);
            }
        }

        return $next($request);
    }
}
