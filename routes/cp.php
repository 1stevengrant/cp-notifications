<?php

use Ghijk\CpNotifications\Http\Controllers\ActiveStackController;
use Ghijk\CpNotifications\Http\Controllers\AcknowledgeNotificationController;
use Ghijk\CpNotifications\Http\Controllers\SnoozeNotificationController;
use Ghijk\CpNotifications\Http\Controllers\BlockingInterstitialController;
use Ghijk\CpNotifications\Http\Controllers\InboxController;
use Ghijk\CpNotifications\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('cp-notifications')->name('cp-notifications.')->group(function (): void {
    Route::get('inbox', InboxController::class)->name('inbox');
    Route::view('manage', 'cp-notifications::manage')->name('manage');
    Route::get('reports', [ReportController::class, 'index'])->name('reports');
    Route::get('reports/{notification}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('acknowledge', BlockingInterstitialController::class)->name('acknowledge');
    Route::get('api/stack', ActiveStackController::class)->name('api.stack');
    Route::post('api/notifications/{notification}/acknowledge', AcknowledgeNotificationController::class)
        ->name('api.notifications.acknowledge');
    Route::post('api/notifications/{notification}/snooze', SnoozeNotificationController::class)
        ->name('api.notifications.snooze');
});
