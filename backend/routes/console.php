<?php

use App\Services\InvoiceService;
use App\Services\ReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Finance (module 9.18): mark unpaid invoices past their due date as overdue.
// Reads already refresh this; the schedule keeps stored statuses current for
// reports once a scheduler runs (`php artisan schedule:work`).
Artisan::command('invoices:refresh-statuses', function (InvoiceService $invoices) {
    $this->info($invoices->refreshOverdue().' invoice(s) marked overdue.');
})->purpose('Mark unpaid invoices past their due date as overdue');

Schedule::command('invoices:refresh-statuses')->dailyAt('00:10');

// Notifications (modules 9.20 / 9.21): remind students of unsubmitted
// assignments due within 24 hours, once a day. Runs in the `scheduler`
// container; delivery happens in the `queue` container.
Artisan::command('notifications:assignment-reminders', function (ReminderService $reminders) {
    $this->info($reminders->assignmentsDueSoon().' reminder(s) queued.');
})->purpose('Remind students of assignments due within 24 hours');

Schedule::command('notifications:assignment-reminders')->dailyAt('07:00');
