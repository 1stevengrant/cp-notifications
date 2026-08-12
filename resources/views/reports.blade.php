@extends('statamic::layout')

@section('title', __('Notification Reports'))

@section('content')
    <div class="cp-notification-page">
        <header class="cp-notification-page__header">
            <div>
                <h1 class="cp-notification-page__heading">{{ __('Notification Reports') }}</h1>
                <p class="cp-notification-page__intro">
                    {{ __('Review acknowledgement progress and export recipient records.') }}
                </p>
            </div>
        </header>

        @if ($notifications->isEmpty())
            <div class="cp-notification-card cp-notification-empty">
                <h2 class="font-semibold">{{ __('No notification reports yet') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('Published notifications will appear here.') }}
                </p>
            </div>
        @else
            <div class="cp-notification-card">
                <div class="cp-notification-table-wrap">
                    <table class="cp-notification-table">
                        <thead>
                            <tr>
                                <th>{{ __('Notification') }}</th>
                                <th class="cp-notification-table__actions">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($notifications as $notification)
                                <tr>
                                    <td>
                                        <a class="cp-notification-table__link" href="{{ cp_route('cp-notifications.reports.show', $notification->id()) }}">
                                            {{ $notification->get('title') }}
                                        </a>
                                    </td>
                                    <td class="cp-notification-table__actions">
                                        <a class="cp-notification-button" href="{{ cp_route('cp-notifications.reports.show', $notification->id()) }}">
                                            {{ __('View report') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
