<?php

use Ghijk\CpNotifications\Http\Controllers\ActiveStackController;
use Ghijk\CpNotifications\Http\Controllers\AcknowledgeNotificationController;
use Ghijk\CpNotifications\Http\Controllers\SnoozeNotificationController;
use Ghijk\CpNotifications\Http\Controllers\BlockingInterstitialController;
use Illuminate\Support\Facades\Route;

Route::prefix('cp-notifications')->name('cp-notifications.')->group(function (): void {
    Route::view('inbox', 'cp-notifications::inbox')->name('inbox');
    Route::view('manage', 'cp-notifications::manage')->name('manage');
    Route::view('reports', 'cp-notifications::reports')->name('reports');
    Route::get('acknowledge', BlockingInterstitialController::class)->name('acknowledge');
    Route::get('api/stack', ActiveStackController::class)->name('api.stack');
    Route::post('api/notifications/{notification}/acknowledge', AcknowledgeNotificationController::class)
        ->name('api.notifications.acknowledge');
    Route::post('api/notifications/{notification}/snooze', SnoozeNotificationController::class)
        ->name('api.notifications.snooze');
});
