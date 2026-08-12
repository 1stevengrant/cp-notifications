<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Listeners\ValidateNotificationAudience;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Statamic\Events\EntrySaving;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

class ValidateNotificationAudienceTest extends TestCase
{
    public function test_published_notifications_require_an_audience_target(): void
    {
        $entry = $this->notification(published: true, audience: []);

        try {
            (new ValidateNotificationAudience)->handle(new EntrySaving($entry));
            $this->fail('Expected audience validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('audience', $exception->errors());
        }
    }

    public function test_drafts_may_have_an_incomplete_audience(): void
    {
        $entry = $this->notification(published: false, audience: []);

        (new ValidateNotificationAudience)->handle(new EntrySaving($entry));

        $this->addToAssertionCount(1);
    }

    #[DataProvider('validAudiences')]
    public function test_each_audience_selector_allows_publication(array $audience): void
    {
        $entry = $this->notification(published: true, audience: $audience);

        (new ValidateNotificationAudience)->handle(new EntrySaving($entry));

        $this->addToAssertionCount(1);
    }

    public static function validAudiences(): array
    {
        return [
            'all users' => [['all' => true]],
            'role' => [['roles' => ['editors']]],
            'group' => [['groups' => ['staff']]],
            'explicit user' => [['users' => ['user-id']]],
        ];
    }

    private function notification(bool $published, array $audience)
    {
        $collection = Collection::make('notifications');

        return Entry::make()
            ->id('notification-id')
            ->collection($collection)
            ->published($published)
            ->data(['audience' => $audience]);
    }
}
