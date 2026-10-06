<?php

use App\Models\Semester;
use App\Services\InboxService;
use App\Services\InvoiceService;
use App\Services\ReminderService;
use App\Services\TuitionInvoiceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
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

// Class reminders (module 9.21, report 48): a Telegram message to a section's
// students and lecturers about CLASS_REMINDER_MINUTES before each meeting, on
// the university's clock (ACADEMIC_TIMEZONE). Each meeting is reminded once a day.
Artisan::command('notifications:class-reminders', function (ReminderService $reminders) {
    $this->info($reminders->classesStartingSoon().' class reminder(s) queued.');
})->purpose('Remind students and lecturers of classes starting soon (Telegram)');

Schedule::command('notifications:class-reminders')->everyFiveMinutes()->withoutOverlapping();

// Telegram linking (report 48): point the bot's webhook at this server, with
// the shared secret Telegram echoes on every call. Needs a public https APP_URL.
Artisan::command('telegram:webhook {--delete : Remove the webhook instead of setting it}', function () {
    $token = (string) config('services.telegram.bot_token');
    $secret = (string) config('services.telegram.webhook_secret');
    if ($token === '' || ($secret === '' && ! $this->option('delete'))) {
        $this->error('Set TELEGRAM_BOT_TOKEN and TELEGRAM_WEBHOOK_SECRET first.');

        return 1;
    }

    $api = rtrim((string) config('services.telegram.api_url'), '/')."/bot{$token}";
    if ($this->option('delete')) {
        Http::timeout(10)->asJson()->post("{$api}/deleteWebhook")->throw();
        $this->info('Telegram webhook removed.');

        return 0;
    }

    $url = rtrim((string) config('app.url'), '/').'/api/telegram/webhook';
    Http::timeout(10)->asJson()->post("{$api}/setWebhook", ['url' => $url, 'secret_token' => $secret, 'allowed_updates' => ['message']])->throw();
    $this->info("Telegram webhook set to {$url}.");

    return 0;
})->purpose('Register (or remove) the Telegram bot webhook for chat linking');

// In-app inbox (report 42): stored notifications are kept for 180 days.
Artisan::command('notifications:prune {--days='.InboxService::RETENTION_DAYS.' : Delete notifications older than this many days}', function (InboxService $inbox) {
    $this->info($inbox->prune(max(1, (int) $this->option('days'))).' notification(s) deleted.');
})->purpose('Delete in-app notifications past the retention period');

Schedule::command('notifications:prune')->dailyAt('01:00');

// Tuition (module 9.18): generate semester tuition invoices from course enrollments.
Artisan::command('tuition:generate {semester : ID or code of the semester} {--due-date= : Due date for generated invoices (YYYY-MM-DD)} {--rate= : Override rate per credit} {--department= : Filter by department ID} {--program= : Filter by program ID} {--dry-run : Run in preview mode without creating invoices}', function (TuitionInvoiceService $service) {
    $semesterInput = $this->argument('semester');
    $semester = is_numeric($semesterInput)
        ? Semester::query()->find($semesterInput)
        : Semester::query()->where('code', $semesterInput)->first();

    if (! $semester) {
        $this->error("Semester [{$semesterInput}] not found.");

        return 1;
    }

    $options = [
        'due_date' => $this->option('due-date'),
        'rate_per_credit' => $this->option('rate'),
        'department_id' => $this->option('department'),
        'program_id' => $this->option('program'),
        'dry_run' => (bool) $this->option('dry-run'),
    ];

    $this->info("Processing tuition invoices for semester: {$semester->name} ({$semester->code})");
    $result = $service->generate($semester, $options);

    $this->table(
        ['Metric', 'Value'],
        [
            ['Mode', $result['dry_run'] ? 'Dry run (preview)' : 'Live execution'],
            ['Total enrolled students', (string) $result['total_students']],
            ['Invoices generated', (string) $result['generated_count']],
            ['Skipped (already billed/no credits)', (string) $result['skipped_count']],
            ['Total billable credits', (string) $result['total_credits']],
            ['Total billed amount', '$'.number_format($result['total_amount'], 2)],
        ]
    );

    return 0;
})->purpose('Generate automatic tuition invoices for enrolled students in a semester');
