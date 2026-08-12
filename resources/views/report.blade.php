<header class="mb-6">
    <a href="{{ cp_route('cp-notifications.reports') }}">{{ __('Notification Reports') }}</a>
    <h1>{{ $notification->get('title') }}</h1>
</header>

<div class="card p-4" data-testid="notification-report">
    <p>{{ __('Recipient delivery details will appear here.') }}</p>
</div>
