<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Stevebauman\Location\Facades\Location;

class SetLocaleByIp
{
    public function handle(Request $request, Closure $next)
    {
        if (session()->has('locale')) {
            App::setLocale(session('locale'));
            return $next($request);
        }

        $position = Location::get($request->ip());

        $locale = 'en';

        if ($position && $position->countryCode) {
            $arabicCountries = [
                'SA', 'EG', 'AE', 'IQ', 'SY', 'JO', 'KW', 'QA', 'BH', 'OM', 
                'LB', 'PS', 'YE', 'LY', 'TN', 'DZ', 'MA', 'SD', 'MR', 'SO', 'DJ', 'KM'
            ];

            if (in_array(strtoupper($position->countryCode), $arabicCountries)) {
                $locale = 'ar';
            }
        }

        App::setLocale($locale);
        session()->put('locale', $locale);

        return $next($request);
    }
}