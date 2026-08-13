<?php

namespace Ghijk\CpNotifications\Content;

use RuntimeException;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;

class NotificationCollectionInstaller
{
    public function install(): CollectionContract
    {
        if ($collection = Collection::find('notifications')) {
            if ($collection->routes()->filter()->isNotEmpty()) {
                throw new RuntimeException(
                    'The existing notifications collection is routed and cannot be managed by CP Notifications.',
                );
            }

            $this->configureCollection($collection);
            $this->installBlueprint();

            return $collection;
        }

        $collection = tap(
            Collection::make('notifications')
                ->title('Notifications')
                ->routes([])
                ->sites([Site::default()->handle()])
                ->propagate(false)
                ->revisionsEnabled(true)
                ->defaultPublishState(false)
                ->requiresSlugs(false)
                ->sortField('start_date')
                ->sortDirection('desc'),
            fn (CollectionContract $collection) => $collection->save(),
        );

        $this->installBlueprint();

        return $collection;
    }

    private function configureCollection(CollectionContract $collection): void
    {
        $collection
            ->sortField('start_date')
            ->sortDirection('desc')
            ->save();
    }

    private function installBlueprint(): void
    {
        Blueprint::make('notification')
            ->setNamespace('collections.notifications')
            ->setContents([
                'title' => 'Notification',
                'tabs' => [
                    'main' => [
                        'display' => 'Notice',
                        'sections' => [
                            [
                                'fields' => [
                                    ['handle' => 'title', 'field' => ['type' => 'text', 'required' => true, 'listable' => true, 'instructions' => 'A short, specific heading shown in the notification modal, Inbox, and reports.']],
                                    ['handle' => 'notification_status', 'field' => [
                                        'type' => 'select',
                                        'display' => 'Status',
                                        'instructions' => 'Calculated automatically from publication state, schedule, and acknowledgements. Locked notices can no longer be edited or deleted.',
                                        'visibility' => 'computed',
                                        'listable' => true,
                                        'options' => [
                                            'draft' => 'Draft',
                                            'scheduled' => 'Scheduled',
                                            'active' => 'Active',
                                            'expired' => 'Expired',
                                            'locked' => 'Locked',
                                        ],
                                    ]],
                                    ['handle' => 'body', 'field' => ['type' => 'bard', 'display' => 'Body', 'required' => true, 'listable' => false, 'instructions' => 'The full message users must read. Formatting, lists, and links are displayed in the control-panel modal.']],
                                    ['handle' => 'severity', 'field' => [
                                        'type' => 'select',
                                        'display' => 'Severity',
                                        'instructions' => 'Controls the visual badge and ordering: Critical appears before Warning, which appears before Info. It does not make a notice blocking.',
                                        'default' => 'info',
                                        'options' => [
                                            'info' => 'Info',
                                            'warning' => 'Warning',
                                            'critical' => 'Critical',
                                        ],
                                        'required' => true,
                                        'listable' => true,
                                    ]],
                                    ['handle' => 'blocking', 'field' => ['type' => 'toggle', 'display' => 'Blocking', 'default' => false, 'listable' => true, 'instructions' => 'Requires acknowledgement before the user can continue in strict enforcement mode. Blocking notices cannot be snoozed.']],
                                    ['handle' => 'snoozeable', 'field' => ['type' => 'toggle', 'display' => 'Snoozeable', 'default' => false, 'listable' => 'hidden', 'instructions' => 'Allows an advisory notice to be postponed once for 24 hours. This is automatically disabled when Blocking is enabled.']],
                                    ['handle' => 'priority', 'field' => ['type' => 'integer', 'display' => 'Priority', 'listable' => 'hidden', 'instructions' => 'Optional ordering override. Lower numbers appear first; leave blank to order by severity and start date.']],
                                ],
                            ],
                        ],
                    ],
                    'audience' => [
                        'display' => 'Audience',
                        'sections' => [
                            [
                                'fields' => [
                                    ['handle' => 'audience', 'field' => [
                                        'type' => 'group',
                                        'display' => 'Audience',
                                        'instructions' => 'Choose who receives this notice. A user matching any selected option is included only once; at least one target is required before publishing.',
                                        'listable' => false,
                                        'fields' => [
                                            ['handle' => 'all', 'field' => ['type' => 'toggle', 'display' => 'All users', 'default' => false, 'instructions' => 'Targets every control-panel user, including users added after publication.']],
                                            ['handle' => 'roles', 'field' => ['type' => 'user_roles', 'display' => 'Roles', 'instructions' => 'Targets users who currently have any selected role. Membership is evaluated live.']],
                                            ['handle' => 'groups', 'field' => ['type' => 'user_groups', 'display' => 'Groups', 'instructions' => 'Targets users who currently belong to any selected group. Membership is evaluated live.']],
                                            ['handle' => 'users', 'field' => ['type' => 'users', 'display' => 'Users', 'instructions' => 'Targets specific users in addition to any selected roles or groups.']],
                                        ],
                                    ]],
                                ],
                            ],
                        ],
                    ],
                    'scheduling' => [
                        'display' => 'Scheduling',
                        'sections' => [
                            [
                                'fields' => [
                                    ['handle' => 'start_date', 'field' => ['type' => 'date', 'display' => 'Start date', 'time_enabled' => true, 'required' => true, 'listable' => true, 'instructions' => 'The notice becomes active at this date and time, using the application timezone. It must also be published.']],
                                    ['handle' => 'end_date', 'field' => ['type' => 'date', 'display' => 'End date', 'time_enabled' => true, 'listable' => true, 'instructions' => 'Optional. The notice stops appearing and stops blocking at this exact time, even if it was not acknowledged. Leave blank for no expiry.']],
                                ],
                            ],
                        ],
                    ],
                    'nudges' => [
                        'display' => 'Nudges',
                        'sections' => [
                            [
                                'fields' => [
                                    ['handle' => 'nudge', 'field' => [
                                        'type' => 'group',
                                        'display' => 'Nudge settings',
                                        'instructions' => 'Optional email reminders for currently targeted users who have not acknowledged this notice. The notification content remains available only in the control panel.',
                                        'listable' => false,
                                        'fields' => [
                                            ['handle' => 'enabled', 'field' => ['type' => 'toggle', 'display' => 'Enabled', 'default' => false, 'instructions' => 'Send automatic reminder emails when the scheduled nudge command runs.']],
                                            ['handle' => 'threshold_hours', 'field' => ['type' => 'integer', 'display' => 'Threshold hours', 'default' => 24, 'instructions' => 'Hours after the notice start date before the first automatic reminder becomes eligible.']],
                                            ['handle' => 'cadence_hours', 'field' => ['type' => 'integer', 'display' => 'Cadence hours', 'instructions' => 'Optional hours between repeat reminders. Leave blank to send only one automatic reminder per user.']],
                                        ],
                                    ]],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->save();
    }
}
