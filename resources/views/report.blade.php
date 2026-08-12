<header class="mb-6">
    <a href="{{ cp_route('cp-notifications.reports') }}">{{ __('Notification Reports') }}</a>
    <h1>{{ $notification->get('title') }}</h1>
</header>

<div class="card p-4" data-testid="notification-report">
    <h2>{{ __('Current audience') }}</h2>

    @if ($targetedUsers->isEmpty())
        <p>{{ __('No users are currently targeted.') }}</p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('User') }}</th>
                    <th>{{ __('Email') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($targetedUsers as $targetedUser)
                    <tr>
                        <td>{{ $targetedUser->name() }}</td>
                        <td>{{ $targetedUser->email() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
