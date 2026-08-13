<?php

namespace Ghijk\CpNotifications\Http\Controllers;

use Ghijk\CpNotifications\Jobs\SendNotificationNudges;
use Ghijk\CpNotifications\Reports\NotificationReportResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController
{
    public function index(Request $request): View
    {
        $this->authorize($request);
        $collection = Collection::find('notifications');

        return view('cp-notifications::reports', [
            'notifications' => $collection
                ? $collection->queryEntries()->where('site', Site::default()->handle())->get()
                : collect(),
        ]);
    }

    public function show(Request $request, string $notification, NotificationReportResolver $report): View
    {
        $this->authorize($request);
        $entry = $this->notification($notification);

        return view('cp-notifications::report', [
            'notification' => $entry,
            'rows' => $report->resolve($entry),
        ]);
    }

    public function export(Request $request, string $notification, NotificationReportResolver $report): StreamedResponse
    {
        $this->authorize($request);
        $entry = $this->notification($notification);
        $rows = $report->resolve($entry);

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['User', 'Email', 'Audience', 'Status', 'Acknowledged at', 'Snooze']);

            foreach ($rows as $row) {
                $snooze = ! $row['snooze']
                    ? 'Not used'
                    : ($row['snooze_active']
                        ? 'Active until '.$row['snooze']->snoozedUntil->format('Y-m-d H:i:s')
                        : 'Used (ended '.$row['snooze']->snoozedUntil->format('Y-m-d H:i:s').')');

                fputcsv($stream, array_map($this->safeCsvCell(...), [
                    $row['user']?->name() ?? ($row['user_id'] ?? 'Deleted user'),
                    $row['user']?->email() ?? '',
                    $row['currently_targeted'] ? 'Current' : 'Former',
                    $row['acknowledgement'] ? 'Acknowledged' : 'Pending',
                    $row['acknowledgement']?->acknowledgedAt->format('Y-m-d H:i:s') ?? '',
                    $snooze,
                ]));
            }

            fclose($stream);
        }, 'notification-'.$entry->id().'-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function remind(Request $request, string $notification): RedirectResponse
    {
        $this->authorize($request);
        $entry = $this->notification($notification);

        SendNotificationNudges::dispatch((string) $entry->id(), true);

        return back()->with('success', __('Reminders are being sent to users who have not acknowledged this notification.'));
    }

    private function notification(string $id)
    {
        $entry = Entry::find($id);

        abort_unless(
            $entry
                && $entry->collectionHandle() === 'notifications'
                && $entry->locale() === Site::default()->handle(),
            404,
        );

        return $entry;
    }

    private function safeCsvCell(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }

    private function authorize(Request $request): void
    {
        abort_unless($request->user()?->can('view notification reports'), 403);
    }
}
