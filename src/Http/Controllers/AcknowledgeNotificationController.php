<?php

namespace Ghijk\CpNotifications\Http\Controllers;

use Ghijk\CpNotifications\Audience\AudienceMatcher;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

final class AcknowledgeNotificationController
{
    public function __invoke(
        string $notification,
        Request $request,
        AudienceMatcher $audience,
        ActiveWindow $window,
        AcknowledgementRepository $acknowledgements,
    ): JsonResponse {
        $user = User::current();
        abort_unless($user, 401);

        if ($request->input('confirmed') !== true) {
            throw ValidationException::withMessages([
                'confirmed' => 'You must explicitly confirm that you have read and understand this notification.',
            ]);
        }

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
