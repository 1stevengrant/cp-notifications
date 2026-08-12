<?php

namespace Ghijk\CpNotifications\Tests\Pest\ValidateNotificationAudienceTest;

use Ghijk\CpNotifications\Listeners\ValidateNotificationAudience;
use Illuminate\Validation\ValidationException;
use Statamic\Events\EntrySaving;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

test('published notifications require an audience target', function () {
    $entry = notification(published: true, audience: []);

    try {
        (new ValidateNotificationAudience)->handle(new EntrySaving($entry));
        $this->fail('Expected audience validation to fail.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('audience');
    }
});

test('drafts may have an incomplete audience', function () {
    $entry = notification(published: false, audience: []);

    (new ValidateNotificationAudience)->handle(new EntrySaving($entry));

    $this->addToAssertionCount(1);
});

test('each audience selector allows publication', function (array $audience) {
    $entry = notification(published: true, audience: $audience);

    (new ValidateNotificationAudience)->handle(new EntrySaving($entry));

    $this->addToAssertionCount(1);
})->with('validAudiences');

dataset('validAudiences', function () {
    return [
        'all users' => [['all' => true]],
        'role' => [['roles' => ['editors']]],
        'group' => [['groups' => ['staff']]],
        'explicit user' => [['users' => ['user-id']]],
    ];
});

function notification(bool $published, array $audience)
{
    $collection = Collection::make('notifications');

    return Entry::make()
        ->id('notification-id')
        ->collection($collection)
        ->published($published)
        ->data(['audience' => $audience]);
}
