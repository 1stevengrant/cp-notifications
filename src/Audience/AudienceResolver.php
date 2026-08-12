<?php

namespace Ghijk\CpNotifications\Audience;

use Statamic\Auth\UserCollection;
use Statamic\Contracts\Auth\UserRepository;
use Statamic\Contracts\Entries\Entry;

final class AudienceResolver
{
    public function __construct(
        private UserRepository $users,
        private AudienceMatcher $matcher,
    ) {}

    public function resolve(Entry|array $notification): UserCollection
    {
        return $this->users->all()
            ->filter(fn ($user): bool => $this->matcher->matches($notification, $user))
            ->unique(fn ($user): string => (string) $user->id())
            ->values();
    }
}
