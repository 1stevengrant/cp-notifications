<?php

namespace Ghijk\CpNotifications\Tests\Pest\ControlPanelViewsTest;

use Illuminate\Support\Facades\Blade;

test('dashboard pages use the statamic control panel shell', function () {
    foreach (['inbox', 'manage', 'reports', 'report', 'blocking'] as $view) {
        $markup = file_get_contents(__DIR__."/../resources/views/{$view}.blade.php");

        $this->assertStringContainsString("@extends('statamic::layout')", $markup, $view);
        $this->assertStringContainsString("@section('title'", $markup, $view);
        $this->assertStringContainsString("@section('content')", $markup, $view);
        $this->assertStringContainsString($view === 'blocking' ? 'max-w-page mx-auto' : 'cp-notification-page', $markup, $view);
    }
});

test('dashboard pages use statamic content patterns', function () {
    $inbox = file_get_contents(__DIR__.'/../resources/views/inbox.blade.php');
    $manage = file_get_contents(__DIR__.'/../resources/views/manage.blade.php');
    $reports = file_get_contents(__DIR__.'/../resources/views/reports.blade.php');
    $report = file_get_contents(__DIR__.'/../resources/views/report.blade.php');

    $this->assertStringContainsString('cp-notification-inbox', $inbox);
    $this->assertStringContainsString('cp-notification-badge--{{ $notification->get', $inbox);
    $this->assertStringContainsString("cp-notification-badge--{{ \$item['active'] ? 'active' : 'history' }}", $inbox);
    $this->assertStringContainsString('cp-notification-button--primary', $manage);
    $this->assertStringContainsString('cp-notification-card', $manage);
    $this->assertStringContainsString('cp-notification-card', $reports);
    $this->assertStringContainsString('cp-notification-table', $reports);
    $this->assertStringContainsString('cp-notification-button', $reports);
    $this->assertStringContainsString('cp-notification-button--primary', $report);
    $this->assertStringContainsString('cp-notification-table', $report);
    $this->assertStringContainsString('cp-notification-badge', $report);
});

test('dashboard page templates compile', function () {
    foreach (['inbox', 'manage', 'reports', 'report', 'blocking'] as $view) {
        $markup = file_get_contents(__DIR__."/../resources/views/{$view}.blade.php");

        expect(Blade::compileString($markup))->not->toBeEmpty($view);
    }
});
