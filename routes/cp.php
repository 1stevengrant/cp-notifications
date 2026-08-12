<?php

use Illuminate\Support\Facades\Route;

Route::prefix('cp-notifications')->name('cp-notifications.')->group(function (): void {
    Route::view('inbox', 'cp-notifications::inbox')->name('inbox');
    Route::view('manage', 'cp-notifications::manage')->name('manage');
    Route::view('reports', 'cp-notifications::reports')->name('reports');
});
