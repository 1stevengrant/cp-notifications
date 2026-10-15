<?php

namespace Ghijk\CpNotifications\Tests\Pest\Browser\NotificationModalTest;

use Carbon\CarbonImmutable;
use Ghijk\CpNotifications\Contracts\AcknowledgementRepository;
use Ghijk\CpNotifications\Contracts\SnoozeRepository;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Statamic;

afterEach(function (): void {
    CarbonImmutable::setTestNow();
    Entry::query()->where('collection', 'notifications')->get()->each->deleteQuietly();
    Collection::find('notifications')?->delete();
    File::deleteDirectory(public_path('vendor/statamic/cp'));
    File::deleteDirectory(public_path('vendor/cp-notifications'));
});

test('the modal displays rendered Bard content in a real browser', function (): void {
    config()->set('app.debug', false);
    CarbonImmutable::setTestNow('2026-08-12 12:00:00');
    $user = User::make()->id('browser-user')->email('browser@example.com')->set('super', true);
    $this->actingAs($user);
    Collection::make('notifications')->sites([Site::default()->handle()])->save();
    Entry::make()
        ->id('browser-policy')
        ->collection('notifications')
        ->locale(Site::default()->handle())
        ->published(true)
        ->data([
            'title' => 'Editorial policy',
            'body' => [[
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'text',
                    'text' => 'Every editor must confirm the updated editorial policy.',
                ]],
            ]],
            'severity' => 'critical',
            'blocking' => true,
            'audience' => ['all' => true],
            'start_date' => '2026-08-12 09:00',
        ])
        ->save();
    $this->mock(AcknowledgementRepository::class)->allows('find')->andReturnNull();
    $this->mock(SnoozeRepository::class)->allows('find')->andReturnNull();
    $packageRoot = dirname(__DIR__, 2);
    File::copyDirectory($packageRoot.'/vendor/statamic/cms/resources/dist', public_path('vendor/statamic/cp'));
    File::copyDirectory($packageRoot.'/resources/dist', public_path('vendor/cp-notifications'));
    $this->app->instance(Vite::class, new Vite);
    expect(File::exists(public_path('vendor/statamic/cp/build/manifest.json')))->toBeTrue();
    expect(Statamic::cpViteScripts()->toHtml())->toContain('<script');

    $page = visit(cp_route('cp-notifications.inbox'))
        ->assertSee('Editorial policy')
        ->assertSee('Every editor must confirm the updated editorial policy.')
        ->assertDontSee('[{"type":"paragraph"')
        ->assertNoJavaScriptErrors();

    expect($page->script("getComputedStyle(document.querySelector('.cp-notification-inbox')).borderRadius"))->not->toBe('0px');
    expect($page->script("getComputedStyle(document.querySelector('.cp-notification-inbox__content')).display"))->toBe('grid');
    expect($page->script("getComputedStyle(document.querySelector('.cp-notification-badge--critical')).backgroundColor"))->not->toBe('rgba(0, 0, 0, 0)');

    $page->script(<<<'JS'
        (() => {
            const licensingAlert = document.createElement('div');
            licensingAlert.id = 'licensing-alert';
            licensingAlert.setAttribute('role', 'dialog');
            licensingAlert.setAttribute('aria-modal', 'true');
            licensingAlert.style.cssText = 'position: fixed; inset: 2rem; z-index: 20000; background: white;';
            licensingAlert.innerHTML = '<button>Snooze licensing alert</button>';
            licensingAlert.querySelector('button').addEventListener('click', () => licensingAlert.remove());
            document.body.append(licensingAlert);
        })()
        JS);

    $page->assertPresent('#licensing-alert')
        ->assertMissing('[data-testid="cp-notification-current"]')
        ->click('#licensing-alert button')
        ->assertVisible('[data-testid="cp-notification-current"]')
        ->assertNoJavaScriptErrors();
})->group('browser');
