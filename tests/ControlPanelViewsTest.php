<?php

namespace Ghijk\CpNotifications\Tests;

use Illuminate\Support\Facades\Blade;

class ControlPanelViewsTest extends TestCase
{
    public function test_dashboard_pages_use_the_statamic_control_panel_shell(): void
    {
        foreach (['inbox', 'manage', 'reports', 'report', 'blocking'] as $view) {
            $markup = file_get_contents(__DIR__."/../resources/views/{$view}.blade.php");

            $this->assertStringContainsString("@extends('statamic::layout')", $markup, $view);
            $this->assertStringContainsString("@section('title'", $markup, $view);
            $this->assertStringContainsString("@section('content')", $markup, $view);
            $this->assertStringContainsString('max-w-page mx-auto', $markup, $view);
        }
    }

    public function test_dashboard_pages_use_statamic_content_patterns(): void
    {
        $inbox = file_get_contents(__DIR__.'/../resources/views/inbox.blade.php');
        $manage = file_get_contents(__DIR__.'/../resources/views/manage.blade.php');
        $reports = file_get_contents(__DIR__.'/../resources/views/reports.blade.php');
        $report = file_get_contents(__DIR__.'/../resources/views/report.blade.php');

        $this->assertStringContainsString('card p-0 overflow-hidden', $inbox);
        $this->assertStringContainsString('btn-primary', $manage);
        $this->assertStringContainsString('card p-0 overflow-hidden', $reports);
        $this->assertStringContainsString('data-table', $reports);
        $this->assertStringContainsString('overflow-x-auto', $reports);
        $this->assertStringContainsString('btn-primary', $report);
        $this->assertStringContainsString('data-table', $report);
        $this->assertStringContainsString('badge-sm', $report);
    }

    public function test_dashboard_page_templates_compile(): void
    {
        foreach (['inbox', 'manage', 'reports', 'report', 'blocking'] as $view) {
            $markup = file_get_contents(__DIR__."/../resources/views/{$view}.blade.php");

            $this->assertNotEmpty(Blade::compileString($markup), $view);
        }
    }
}
