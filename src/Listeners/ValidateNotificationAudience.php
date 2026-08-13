<?php

namespace Ghijk\CpNotifications\Listeners;

use Illuminate\Validation\ValidationException;
use Statamic\Events\EntrySaving;

class ValidateNotificationAudience
{
    public function handle(EntrySaving $event): void
    {
        $entry = $event->entry;

        if ($entry->collectionHandle() !== 'notifications' || ! $entry->published()) {
            return;
        }

        $audience = (array) $entry->get('audience', []);
        $hasTarget = ($audience['all'] ?? false) === true
            || collect(['roles', 'groups', 'users'])
                ->contains(fn (string $key) => collect($audience[$key] ?? [])->filter()->isNotEmpty());

        if (! $hasTarget) {
            throw ValidationException::withMessages([
                'audience' => 'Select all users or at least one role, group, or user before publishing.',
            ]);
        }
    }
}
