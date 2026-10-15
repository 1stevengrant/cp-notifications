<?php

namespace Ghijk\CpNotifications\Http\Middleware;

use Closure;
use Ghijk\CpNotifications\Notifications\BlockingNoticeResolver;
use Illuminate\Http\Request;
use Statamic\Auth\User as StatamicUser;
use Statamic\Contracts\Auth\User as UserContract;
use Statamic\Facades\User;
use Symfony\Component\HttpFoundation\Response;

final class EnforceBlockingNotifications
{
    public function __construct(private BlockingNoticeResolver $blocking) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (config('cp-notifications.enforcement') !== 'strict') {
            return $next($request);
        }

        $user = User::current();

        if (! $user || $user->can('bypass notifications') || $this->isExempt($request)) {
            return $next($request);
        }

        if ($this->mustFinishTwoFactorSetup($user)) {
            return $next($request);
        }

        if ($this->blocking->resolve($user)->isNotEmpty()) {
            return redirect()->route('statamic.cp.cp-notifications.acknowledge');
        }

        return $next($request);
    }

    /**
     * Statamic sends these users to its two factor setup page, so gating them
     * here too would bounce them between the two pages forever.
     */
    private function mustFinishTwoFactorSetup(UserContract $user): bool
    {
        return $user instanceof StatamicUser
            && $user->isTwoFactorAuthenticationRequired()
            && ! $user->hasEnabledTwoFactorAuthentication()
            && ! session()->has('statamic_impersonated_by');
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
