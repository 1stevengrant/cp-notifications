<?php

namespace Ghijk\CpNotifications\Http\Controllers;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Illuminate\Http\JsonResponse;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

final class AcknowledgeNotificationController
{
    public function __invoke(
        string $notification,
        AudienceMatcher $audience,
        ActiveWindow $window,
        AcknowledgementRepository $acknowledgements,
    ): JsonResponse {
        $user = User::current();
        abort_unless($user, 401);

        $entry = Entry::find($notification);
        abort_unless($entry && $entry->collectionHandle() === 'notifications', 404);

        $existing = $acknowledgements->find((string) $entry->id(), (string) $user->id());

        if (! $existing) {
            abort_unless($window->isActive($entry) && $audience->matches($entry, $user), 409);
        }

        $acknowledgement = $existing ?? $acknowledgements->record(
            (string) $entry->id(),
            (string) $user->id(),
        );

        return response()->json([
            'data' => $acknowledgement->toArray(),
        ]);
    }
}
