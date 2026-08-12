@extends('statamic::layout')

@section('title', $notification->get('title'))

@section('content')
    <div class="max-w-page mx-auto px-4 py-6 md:px-8">
        <header class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a class="text-sm" href="{{ cp_route('cp-notifications.reports') }}">
                    &larr; {{ __('Notification Reports') }}
                </a>
                <h1 class="mt-2 text-2xl font-bold">{{ $notification->get('title') }}</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ trans_choice('{0} No recipient records|{1} :count recipient record|[2,*] :count recipient records', $rows->count(), ['count' => $rows->count()]) }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a class="btn" href="{{ cp_route('cp-notifications.reports.export', $notification->id()) }}">
                    {{ __('Export CSV') }}
                </a>
                <form method="POST" action="{{ cp_route('cp-notifications.reports.remind', $notification->id()) }}">
                    @csrf
                    <button class="btn-primary" type="submit">{{ __('Remind non-ackers') }}</button>
                </form>
            </div>
        </header>

        <section class="card p-0 overflow-hidden" data-testid="notification-report" aria-labelledby="current-audience-heading">
            <div class="px-5 py-4 border-b dark:border-gray-700">
                <h2 id="current-audience-heading" class="font-semibold">{{ __('Current audience') }}</h2>
            </div>

            @if ($rows->isEmpty())
                <div class="p-8 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('No users are currently targeted.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Audience') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Acknowledged at') }}</th>
                                <th>{{ __('Snooze') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>
                                        <div class="font-medium">{{ $row['user']?->name() ?? ($row['user_id'] ?? __('Deleted user')) }}</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-300">{{ $row['user']?->email() ?? '—' }}</div>
                                    </td>
                                    <td><span class="badge-sm">{{ $row['currently_targeted'] ? __('Current') : __('Former') }}</span></td>
                                    <td><span class="badge-sm">{{ $row['acknowledgement'] ? __('Acknowledged') : __('Pending') }}</span></td>
                                    <td class="whitespace-nowrap">{{ $row['acknowledgement']?->acknowledgedAt->format('Y-m-d H:i:s') ?? '—' }}</td>
                                    <td class="whitespace-nowrap">
                                        @if (! $row['snooze'])
                                            {{ __('Not used') }}
                                        @elseif ($row['snooze_active'])
                                            {{ __('Active until :time', ['time' => $row['snooze']->snoozedUntil->format('Y-m-d H:i:s')]) }}
                                        @else
                                            {{ __('Used (ended :time)', ['time' => $row['snooze']->snoozedUntil->format('Y-m-d H:i:s')]) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
