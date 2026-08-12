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
                                    ['handle' => 'title', 'field' => ['type' => 'text', 'required' => true, 'listable' => true]],
                                    ['handle' => 'body', 'field' => ['type' => 'bard', 'display' => 'Body', 'required' => true, 'listable' => false]],
                                    ['handle' => 'severity', 'field' => [
                                        'type' => 'select',
                                        'display' => 'Severity',
                                        'default' => 'info',
                                        'options' => [
                                            'info' => 'Info',
                                            'warning' => 'Warning',
                                            'critical' => 'Critical',
                                        ],
                                        'required' => true,
                                        'listable' => true,
                                    ]],
                                    ['handle' => 'blocking', 'field' => ['type' => 'toggle', 'display' => 'Blocking', 'default' => false, 'listable' => true]],
                                    ['handle' => 'snoozeable', 'field' => ['type' => 'toggle', 'display' => 'Snoozeable', 'default' => false, 'listable' => 'hidden']],
                                    ['handle' => 'priority', 'field' => ['type' => 'integer', 'display' => 'Priority', 'listable' => 'hidden']],
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
                                        'listable' => false,
                                        'fields' => [
                                            ['handle' => 'all', 'field' => ['type' => 'toggle', 'display' => 'All users', 'default' => false]],
                                            ['handle' => 'roles', 'field' => ['type' => 'user_roles', 'display' => 'Roles']],
                                            ['handle' => 'groups', 'field' => ['type' => 'user_groups', 'display' => 'Groups']],
                                            ['handle' => 'users', 'field' => ['type' => 'users', 'display' => 'Users']],
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
                                    ['handle' => 'start_date', 'field' => ['type' => 'date', 'display' => 'Start date', 'time_enabled' => true, 'required' => true, 'listable' => true]],
                                    ['handle' => 'end_date', 'field' => ['type' => 'date', 'display' => 'End date', 'time_enabled' => true, 'listable' => true]],
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
                                        'listable' => false,
                                        'fields' => [
                                            ['handle' => 'enabled', 'field' => ['type' => 'toggle', 'display' => 'Enabled', 'default' => false]],
                                            ['handle' => 'threshold_hours', 'field' => ['type' => 'integer', 'display' => 'Threshold hours', 'default' => 24]],
                                            ['handle' => 'cadence_hours', 'field' => ['type' => 'integer', 'display' => 'Cadence hours']],
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
