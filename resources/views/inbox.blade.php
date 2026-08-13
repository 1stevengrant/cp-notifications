@extends('statamic::layout')

@section('title', __('Notification Inbox'))

@section('content')
    <div class="cp-notification-page">
        <header class="cp-notification-page__header">
            <div>
                <h1 class="cp-notification-page__heading">{{ __('Notification Inbox') }}</h1>
                <p class="cp-notification-page__intro">
                    {{ __('Notices targeted to your account, including retained history.') }}
                </p>
            </div>
        </header>

        <div class="cp-notification-inbox" data-testid="notification-inbox">
            @forelse ($notifications as $item)
                @php($notification = $item['notification'])
                <article class="cp-notification-inbox__item">
                    <div class="cp-notification-inbox__content">
                        <div class="cp-notification-inbox__copy">
                            <h2 class="cp-notification-inbox__title">{{ $notification->get('title') }}</h2>
                            <p class="cp-notification-inbox__meta">
                                @if ($item['acknowledgement'])
                                    {{ __('Read :date', ['date' => $item['acknowledgement']->acknowledgedAt->toDayDateTimeString()]) }}
                                @elseif ($item['active'])
                                    {{ __('Awaiting acknowledgement') }}
                                @else
                                    {{ __('Not acknowledged') }}
                                @endif
                            </p>
                        </div>

                        <div class="cp-notification-inbox__badges">
                            <span class="cp-notification-badge cp-notification-badge--{{ $notification->get('severity', 'info') }}">
                                {{ ucfirst($notification->get('severity', 'info')) }}
                            </span>
                            <span class="cp-notification-badge cp-notification-badge--{{ $item['active'] ? 'active' : 'history' }}">
                                {{ $item['active'] ? __('Active') : __('History') }}
                            </span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="cp-notification-inbox__empty">
                    <h2 class="font-semibold">{{ __('Your inbox is clear') }}</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('No notifications are currently targeted to you.') }}
                    </p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
