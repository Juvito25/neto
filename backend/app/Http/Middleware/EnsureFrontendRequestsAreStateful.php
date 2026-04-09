<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;

class EnsureFrontendRequestsAreStateful
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->isStatefulRequest($request)) {
            Sanctum::setCurrentApplicationUrl($request->root());
        }

        return $next($request);
    }

    private function isStatefulRequest(Request $request): bool
    {
        $domain = $request->headers->get('origin') ?: $request->fullUrl();
        
        $statefulDomains = array_merge(
            explode(',', config('sanctum.stateful', '')),
            config('sanctum.stateful_domains', [])
        );

        foreach ($statefulDomains as $statefulDomain) {
            $statefulDomain = trim($statefulDomain);
            if (empty($statefulDomain)) {
                continue;
            }

            if ($this->domainMatches($domain, $statefulDomain)) {
                return true;
            }
        }

        return false;
    }

    private function domainMatches(string $domain, string $pattern): bool
    {
        if (str_starts_with($pattern, 'http')) {
            return str_starts_with($domain, $pattern);
        }

        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/:\d+$/', '', $domain);

        if (str_starts_with($pattern, '.')) {
            return str_ends_with($domain, $pattern);
        }

        return $domain === $pattern || str_ends_with($domain, '.' . $pattern);
    }
}