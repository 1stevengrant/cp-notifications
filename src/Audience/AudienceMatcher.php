<?php

namespace Ghijk\CpNotifications\Audience;

use Illuminate\Support\Arr;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\Entry;

final class AudienceMatcher
{
    public function matches(Entry|array $notification, User $user): bool
    {
        $audience = $notification instanceof Entry
            ? $notification->get('audience', [])
            : ($notification['audience'] ?? $notification);

        if (! is_array($audience)) {
            return false;
        }

        return (bool) ($audience['all'] ?? false)
            || $this->contains($audience['users'] ?? [], $user->id())
            || $this->matchesAny($audience['roles'] ?? [], $user->hasRole(...))
            || $this->matchesAny($audience['groups'] ?? [], $user->isInGroup(...));
    }

    private function contains(mixed $values, string $expected): bool
    {
        return collect(Arr::wrap($values))
            ->contains(fn (mixed $value): bool => $this->handle($value) === $expected);
    }

    private function matchesAny(mixed $values, callable $matcher): bool
    {
        return collect(Arr::wrap($values))
            ->map($this->handle(...))
            ->filter()
            ->contains(fn (string $handle): bool => (bool) $matcher($handle));
    }

    private function handle(mixed $value): ?string
    {
        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, 'handle')) {
            return (string) $value->handle();
        }

        if (is_object($value) && method_exists($value, 'id')) {
            return (string) $value->id();
        }

        return null;
    }
}
