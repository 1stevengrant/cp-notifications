<?php

namespace Ghijk\CpNotifications\Http\Controllers;

use Ghijk\CpNotifications\Notifications\ActiveStackResolver;
use Illuminate\Http\JsonResponse;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Bard;

final class ActiveStackController
{
    public function __invoke(ActiveStackResolver $resolver): JsonResponse
    {
        $user = User::current();

        abort_unless($user, 401);

        $collection = Collection::find('notifications');
        $notifications = $collection
            ? $collection->queryEntries()->where('site', Site::default()->handle())->get()
            : collect();

        $stack = $resolver->resolve($notifications, $user);

        return response()->json([
            'data' => $stack->map(function ($notification): array {
                $bodyHtml = $this->bodyHtml($notification);

                return [
                    'id' => $notification->id(),
                    'title' => $notification->get('title'),
                    'body' => trim(strip_tags($bodyHtml)),
                    'body_html' => $bodyHtml,
                    'severity' => $notification->get('severity', 'info'),
                    'blocking' => (bool) $notification->get('blocking', false),
                    'snoozeable' => (bool) $notification->get('snoozeable', false),
                    'priority' => $notification->get('priority'),
                    'start_date' => $notification->get('start_date'),
                    'end_date' => $notification->get('end_date'),
                ];
            })->values(),
        ]);
    }

    private function bodyHtml($notification): string
    {
        $body = $notification->augmentedValue('body');
        $body = $body instanceof Value ? $body->value() : $body;

        if (is_string($body)) {
            return $body;
        }

        $raw = $notification->get('body');

        return is_array($raw) ? (string) (new Bard)->augment($raw) : '';
    }
}
