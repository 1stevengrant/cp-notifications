<?php

namespace Ghijk\CpNotifications\Http\Middleware;

use Closure;
use Ghijk\CpNotifications\Notifications\BlockingNoticeResolver;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Statamic\Facades\User;

final class EnforceBlockingNotifications
{
    public function __construct(private BlockingNoticeResolver $blocking)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (config('cp-notifications.enforcement') !== 'strict') {
            return $next($request);
        }

        $user = User::current();

        if (! $user || $this->isExempt($request)) {
            return $next($request);
        }

        if ($this->blocking->resolve($user)->isNotEmpty()) {
            return redirect()->route('statamic.cp.cp-notifications.acknowledge');
        }

        return $next($request);
    }

    private function isExempt(Request $request): bool
    {
        return $request->routeIs(
            'statamic.cp.cp-notifications.acknowledge',
            'statamic.cp.cp-notifications.api.*',
            'statamic.cp.logout',
            'statamic.cp.token',
            'statamic.cp.extend',
            'statamic.cp.assets.thumbnails.show',
            'statamic.cp.assets.svgs.show',
            'statamic.cp.assets.pdfs.show',
        );
    }
}
