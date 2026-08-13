@extends('statamic::layout')

@section('title', __('Manage Notifications'))

@section('content')
    <div class="cp-notification-page">
        <header class="cp-notification-page__header">
            <div>
                <h1 class="cp-notification-page__heading">{{ __('Manage Notifications') }}</h1>
                <p class="cp-notification-page__intro">
                    {{ __('Create notices and manage expired notification records.') }}
                </p>
            </div>

            <a class="cp-notification-button cp-notification-button--primary" href="{{ cp_route('collections.show', 'notifications') }}">
                {{ __('Open notification collection') }}
            </a>
        </header>

        @if ($canPurge)
            <section class="cp-notification-card" aria-labelledby="clear-out-heading">
                <div class="cp-notification-card__body">
                    <div class="cp-notification-card__header">
                        <div>
                            <h2 id="clear-out-heading" class="cp-notification-card__title">
                                {{ __('Clear out expired notifications') }}
                            </h2>
                            <p class="cp-notification-card__description">
                                {{ trans_choice('{0} No notifications are currently eligible.|{1} One notification is eligible.|[2,*] :count notifications are eligible.', $purgeCandidates->count(), ['count' => $purgeCandidates->count()]) }}
                            </p>
                        </div>

                        <span class="cp-notification-badge cp-notification-badge--warning">
                            {{ trans_choice('{0} None eligible|{1} :count eligible|[2,*] :count eligible', $purgeCandidates->count(), ['count' => $purgeCandidates->count()]) }}
                        </span>
                    </div>

                    <form class="cp-notification-card__section" method="POST" action="{{ cp_route('cp-notifications.manage.purge') }}">
                        @csrf
                        <label class="cp-notification-confirmation">
                            <input type="checkbox" name="confirmed" value="1" required>
                            <span>{{ __('I understand that eligible notifications will be permanently removed.') }}</span>
                        </label>

                        <button class="cp-notification-button cp-notification-button--danger mt-4" type="submit" @disabled($purgeCandidates->isEmpty())>
                            {{ __('Clear out eligible notifications') }}
                        </button>
                    </form>
                </div>
            </section>
        @endif
    </div>
@endsection
