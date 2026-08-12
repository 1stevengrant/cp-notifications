@extends('statamic::layout')

@section('title', __('Notification Inbox'))

@section('content')
    <div class="max-w-page mx-auto px-4 py-6 md:px-8">
        <header class="mb-6">
            <h1 class="text-2xl font-bold">{{ __('Notification Inbox') }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                {{ __('Notices targeted to your account, including retained history.') }}
            </p>
        </header>

        <div class="card p-0 overflow-hidden" data-testid="notification-inbox">
            @forelse ($notifications as $item)
                @php($notification = $item['notification'])
                <article class="p-5 border-b last:border-b-0 dark:border-gray-700">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold">{{ $notification->get('title') }}</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                @if ($item['acknowledgement'])
                                    {{ __('Read :date', ['date' => $item['acknowledgement']->acknowledgedAt->toDayDateTimeString()]) }}
                                @elseif ($item['active'])
                                    {{ __('Awaiting acknowledgement') }}
                                @else
                                    {{ __('Not acknowledged') }}
                                @endif
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge-sm">{{ ucfirst($notification->get('severity', 'info')) }}</span>
                            <span class="badge-sm">{{ $item['active'] ? __('Active') : __('History') }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="p-8 text-center">
                    <h2 class="font-semibold">{{ __('Your inbox is clear') }}</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('No notifications are currently targeted to you.') }}
                    </p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
