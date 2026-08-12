@extends('statamic::layout')

@section('title', __('Manage Notifications'))

@section('content')
    <div class="max-w-page mx-auto px-4 py-6 md:px-8">
        <header class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ __('Manage Notifications') }}</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('Create notices and manage expired notification records.') }}
                </p>
            </div>

            <a class="btn-primary self-start" href="{{ cp_route('collections.show', 'notifications') }}">
                {{ __('Open notification collection') }}
            </a>
        </header>

        @if ($canPurge)
            <section class="card p-6" aria-labelledby="clear-out-heading">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="max-w-2xl">
                        <h2 id="clear-out-heading" class="text-lg font-semibold">
                            {{ __('Clear out expired notifications') }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ trans_choice('{0} No notifications are currently eligible.|{1} One notification is eligible.|[2,*] :count notifications are eligible.', $purgeCandidates->count(), ['count' => $purgeCandidates->count()]) }}
                        </p>
                    </div>

                    <span class="badge-sm self-start">
                        {{ trans_choice('{0} None eligible|{1} :count eligible|[2,*] :count eligible', $purgeCandidates->count(), ['count' => $purgeCandidates->count()]) }}
                    </span>
                </div>

                <form class="mt-6 border-t pt-5 dark:border-gray-700" method="POST" action="{{ cp_route('cp-notifications.manage.purge') }}">
                    @csrf
                    <label class="flex items-start gap-3 text-sm">
                        <input class="mt-0.5" type="checkbox" name="confirmed" value="1" required>
                        <span>{{ __('I understand that eligible notifications will be permanently removed.') }}</span>
                    </label>

                    <button class="btn-danger mt-4" type="submit" @disabled($purgeCandidates->isEmpty())>
                        {{ __('Clear out eligible notifications') }}
                    </button>
                </form>
            </section>
        @endif
    </div>
@endsection
