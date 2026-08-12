<?php

namespace Ghijk\CpNotifications\Tests\Pest\ControlPanelViewsTest;

use Illuminate\Support\Facades\Blade;

test('dashboard pages use the statamic control panel shell', function () {
    foreach (['inbox', 'manage', 'reports', 'report', 'blocking'] as $view) {
        $markup = file_get_contents(__DIR__."/../resources/views/{$view}.blade.php");

        $this->assertStringContainsString("@extends('statamic::layout')", $markup, $view);
        $this->assertStringContainsString("@section('title'", $markup, $view);
        $this->assertStringContainsString("@section('content')", $markup, $view);
        $this->assertStringContainsString('max-w-page mx-auto', $markup, $view);
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
    $this->assertStringContainsString('btn-primary', $manage);
    $this->assertStringContainsString('card p-0 overflow-hidden', $reports);
    $this->assertStringContainsString('data-table', $reports);
    $this->assertStringContainsString('overflow-x-auto', $reports);
    $this->assertStringContainsString('btn-primary', $report);
    $this->assertStringContainsString('data-table', $report);
    $this->assertStringContainsString('badge-sm', $report);
});

test('dashboard page templates compile', function () {
    foreach (['inbox', 'manage', 'reports', 'report', 'blocking'] as $view) {
        $markup = file_get_contents(__DIR__."/../resources/views/{$view}.blade.php");

        expect(Blade::compileString($markup))->not->toBeEmpty($view);
    }
});
