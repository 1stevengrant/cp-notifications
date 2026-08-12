@extends('statamic::layout')

@section('title', __('Notification acknowledgement required'))

@section('content')
    <div class="max-w-page mx-auto px-4 py-12 md:px-8" data-testid="notification-interstitial">
        <div class="card max-w-2xl mx-auto p-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="badge-sm">{{ ucfirst($notification->get('severity', 'critical')) }}</span>
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">
                    {{ __('Acknowledgement required') }}
                </span>
            </div>

            <h1 class="text-2xl font-bold">{{ $notification->get('title') }}</h1>
            <p class="mt-3 text-gray-700 dark:text-gray-200">
                {{ __('This blocking notification must be acknowledged before you can continue in the control panel.') }}
            </p>
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                {{ __('Use the confirmation dialog to confirm that you have read and understand it.') }}
            </p>
        </div>
    </div>
@endsection
