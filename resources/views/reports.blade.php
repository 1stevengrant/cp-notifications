@extends('statamic::layout')

@section('title', __('Notification Reports'))

@section('content')
    <div class="max-w-page mx-auto px-4 py-6 md:px-8">
        <header class="mb-6">
            <h1 class="text-2xl font-bold">{{ __('Notification Reports') }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                {{ __('Review acknowledgement progress and export recipient records.') }}
            </p>
        </header>

        @if ($notifications->isEmpty())
            <div class="card p-8 text-center">
                <h2 class="font-semibold">{{ __('No notification reports yet') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('Published notifications will appear here.') }}
                </p>
            </div>
        @else
            <div class="card p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Notification') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($notifications as $notification)
                                <tr>
                                    <td>
                                        <a class="font-medium" href="{{ cp_route('cp-notifications.reports.show', $notification->id()) }}">
                                            {{ $notification->get('title') }}
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        <a class="btn" href="{{ cp_route('cp-notifications.reports.show', $notification->id()) }}">
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
