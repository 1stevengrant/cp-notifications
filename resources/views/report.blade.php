<header class="mb-6">
    <a href="{{ cp_route('cp-notifications.reports') }}">{{ __('Notification Reports') }}</a>
    <h1>{{ $notification->get('title') }}</h1>
</header>

<div class="card p-4" data-testid="notification-report">
    <h2>{{ __('Current audience') }}</h2>

    @if ($rows->isEmpty())
        <p>{{ __('No users are currently targeted.') }}</p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('User') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Acknowledged at') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['user']->name() }}</td>
                        <td>{{ $row['user']->email() }}</td>
                        <td>{{ $row['acknowledgement'] ? __('Acknowledged') : __('Pending') }}</td>
                        <td>{{ $row['acknowledgement']?->acknowledgedAt->format('Y-m-d H:i:s') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
