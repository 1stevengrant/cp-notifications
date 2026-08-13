<?php

namespace Ghijk\CpNotifications\Http\Controllers;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Illuminate\Http\JsonResponse;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

final class SnoozeNotificationController
{
    public function __invoke(
        string $notification,
        AudienceMatcher $audience,
        ActiveWindow $window,
        SnoozeRepository $snoozes,
    ): JsonResponse {
        $user = User::current();
        abort_unless($user, 401);

        $entry = Entry::find($notification);
        abort_unless($entry && $entry->collectionHandle() === 'notifications', 404);

        abort_unless(
            $window->isActive($entry)
            && $audience->matches($entry, $user)
            && ! $entry->get('blocking', false)
            && $entry->get('snoozeable', false),
            409,
        );

        abort_if($snoozes->find((string) $entry->id(), (string) $user->id()), 409);

        $snooze = $snoozes->record((string) $entry->id(), (string) $user->id());

        return response()->json(['data' => $snooze->toArray()], 201);
    }
}
