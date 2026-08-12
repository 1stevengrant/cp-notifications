<?php

namespace Ghijk\CpNotifications\Tests;

use Ghijk\CpNotifications\Notifications\ActiveWindow;
use Ghijk\CpNotifications\Nudges\NudgeEligibility;
use PHPUnit\Framework\Attributes\DataProvider;
use Statamic\Entries\Collection;
use Statamic\Entries\Entry;

class NudgeEligibilityTest extends TestCase
{
    #[DataProvider('scheduledCases')]
    public function test_scheduled_eligibility_uses_enabled_threshold_and_optional_cadence(
        array $settings,
        ?string $lastSentAt,
        string $now,
        bool $expected,
    ): void {
        config()->set('app.timezone', 'Pacific/Auckland');
        $notification = $this->notice()->set('nudge', $settings);

        $this->assertSame(
            $expected,
            (new NudgeEligibility(new ActiveWindow))->eligible($notification, $lastSentAt, $now),
        );
    }

    public static function scheduledCases(): array
    {
        return [
            'disabled' => [['enabled' => false, 'threshold_hours' => 24], null, '2026-08-12 12:00', false],
            'before threshold' => [['enabled' => true, 'threshold_hours' => 24], null, '2026-08-11 11:59:59', false],
            'at threshold' => [['enabled' => true, 'threshold_hours' => 24], null, '2026-08-11 12:00', true],
            'one shot already sent' => [['enabled' => true, 'threshold_hours' => 24], '2026-08-11 12:00', '2026-08-12 12:00', false],
            'before cadence' => [['enabled' => true, 'threshold_hours' => 24, 'cadence_hours' => 24], '2026-08-11 12:00', '2026-08-12 11:59:59', false],
            'at cadence' => [['enabled' => true, 'threshold_hours' => 24, 'cadence_hours' => 24], '2026-08-11 12:00', '2026-08-12 12:00', true],
        ];
    }

    public function test_manual_reminders_bypass_settings_but_require_an_active_notice(): void
    {
        $eligibility = new NudgeEligibility(new ActiveWindow);
        $notification = $this->notice()->set('nudge', ['enabled' => false]);

        $this->assertTrue($eligibility->eligible($notification, null, '2026-08-10 12:00', true));
        $this->assertFalse($eligibility->eligible($notification, null, '2026-08-13 12:00', true));
    }

    private function notice(): Entry
    {
        return (new Entry)
            ->id('notice-1')
            ->collection(Collection::make('notifications'))
            ->published(true)
            ->data([
                'start_date' => '2026-08-10 12:00',
                'end_date' => '2026-08-13 12:00',
            ]);
    }
}
