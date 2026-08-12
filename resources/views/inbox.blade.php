@extends('statamic::layout')

@section('title', __('Notification Inbox'))

@section('content')
    <header class="mb-6">
        <h1>{{ __('Notification Inbox') }}</h1>
    </header>

    <div class="card p-0" data-testid="notification-inbox">
        @forelse ($notifications as $notification)
            <article class="p-6 border-b last:border-b-0">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="font-bold text-lg">{{ $notification->get('title') }}</h2>
                    <span class="badge-sm">{{ ucfirst($notification->get('severity', 'info')) }}</span>
                </div>
            </article>
        @empty
            <p class="p-6 text-gray-600">{{ __('No notifications are currently targeted to you.') }}</p>
        @endforelse
    </div>
@endsection
