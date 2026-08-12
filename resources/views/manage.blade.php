<header class="mb-6">
    <h1>Manage Notifications</h1>
</header>

<p>
    <a class="btn" href="{{ cp_route('collections.show', 'notifications') }}">
        {{ __('Open notification collection') }}
    </a>
</p>

@if ($canPurge)
    <div class="card p-4 mt-6">
        <h2>{{ __('Clear out expired notifications') }}</h2>
        <p>{{ trans_choice('{0} No notifications are currently eligible.|{1} One notification is eligible.|[2,*] :count notifications are eligible.', $purgeCandidates->count(), ['count' => $purgeCandidates->count()]) }}</p>

        <form method="POST" action="{{ cp_route('cp-notifications.manage.purge') }}">
            @csrf
            <label>
                <input type="checkbox" name="confirmed" value="1" required>
                {{ __('I understand that eligible notifications will be permanently removed.') }}
            </label>
            <button class="btn-danger mt-3" type="submit" @disabled($purgeCandidates->isEmpty())>
                {{ __('Clear out eligible notifications') }}
            </button>
        </form>
    </div>
@endif
