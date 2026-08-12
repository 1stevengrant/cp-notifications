@extends('statamic::layout')

@section('title', __('Notification acknowledgement required'))

@section('content')
    <div class="max-w-2xl mx-auto py-12" data-testid="notification-interstitial">
        <div class="card p-8">
            <p class="text-sm font-semibold uppercase mb-2">{{ __('Acknowledgement required') }}</p>
            <h1 class="text-2xl font-bold mb-4">{{ $notification->get('title') }}</h1>
            <p>{{ __('This blocking notification must be acknowledged before you can continue in the control panel.') }}</p>
            <p class="mt-4">{{ __('Use the confirmation dialog to confirm that you have read and understand it.') }}</p>
        </div>
    </div>
@endsection
