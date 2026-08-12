<header class="mb-6">
    <h1>Notification Reports</h1>
</header>

@if ($notifications->isEmpty())
    <p>{{ __('No notifications are available to report.') }}</p>
@else
    <div class="card p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('Notification') }}</th>
                    <th>{{ __('Report') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($notifications as $notification)
                    <tr>
                        <td>{{ $notification->get('title') }}</td>
                        <td>
                            <a href="{{ cp_route('cp-notifications.reports.show', $notification->id()) }}">
                                {{ __('View report') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
