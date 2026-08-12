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

        if (! $user || $request->routeIs(
            'statamic.cp.cp-notifications.acknowledge',
            'statamic.cp.cp-notifications.api.*',
        )) {
            return $next($request);
        }

        if ($this->blocking->resolve($user)->isNotEmpty()) {
            return redirect()->route('statamic.cp.cp-notifications.acknowledge');
        }

        return $next($request);
    }
}
