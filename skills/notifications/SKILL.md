---
name: educore-notifications
description: EduCore notifications - Email and Telegram channels via Laravel Notifications, queued delivery, retries, failure handling, user preferences. Consult for any notification work.
---

# EduCore Notifications

EduCore sends notifications via **Email** and **Telegram**, using Laravel
Notifications dispatched on the Redis queue.

## When to use

- Implementing or changing notifications (announcements, reminders, document
  status, registration confirmations, password messages).

## Channels

- **Email** — important announcements, document status, registration
  confirmations, password-related messages, administrative notices.
- **Telegram** — announcements, class/assignment reminders, important academic
  notifications.
- Built via Laravel Notifications (`app/Notifications`); one notification class
  may define several channels.

## Dispatch & queueing

- Notifications go **on the queue** (Redis; `QUEUE_CONNECTION=redis`) when
  there is any chance of delay/volume (batch announcements, reminders).
- Dispatch with clear queue names (e.g. `notifications`, `default`).
- Do not send external email/Telegram synchronously inside a request if avoidable.

## Templates

- Email: Markdown mailables/notifications (`resources/views/mail`) with a
  consistent EduCore header/footer.
- Telegram: resolve the target chat via a stored `chat_id` (see "User
  preferences") and send with the Telegram bot (see
  `skills/security` — credentials via env/GitHub secrets only).

## Retries & failure handling

- Configure sensible `attempts`/timeout on the queue for the notification jobs.
- Use the `failed` queue handling to log failures:
  - Log the error and channel.
  - Do not retry forever.
  - Surface repeated failures to audit (see `skills/audit-logging`).
- On notification failure, keep the source record (e.g. announcement) intact and
  retry the job, not the whole workflow.

## User preferences

- Users may opt out of non-critical channels (e.g. Telegram reminders) while
  critical ones (document approval, password reset) remain mandatory.
- Store preferences (e.g. `telegram_chat_id`, `notify_by_email`, `notify_by_telegram`)
  on the user profile model — nullable.
- Respect preferences in dispatchers; do not send where the user opted out.

## Telegram integration

- Bot token and (optionally) chat id must come from **environment variables /
  GitHub Secrets** — never hardcoded.
- `config/services.php` reads `telegram.token` from `.env`
  (`TELEGRAM_BOT_TOKEN`, etc.).
- CI workflows already send Telegram notifications to the team — do not confuse
  those with in-app user notifications.

## Notification events (starters)

- Registration confirmed
- Document request status change
- Announcement published (targeted audience)
- Assignment deadline approaching
- Grade published
- Payment recorded / invoice due

## Prohibitions

- DO NOT hardcode the Telegram bot token or any credential.
- DO NOT block the request on a retrying external call without a queue.
- DO NOT email/Telegram without a notification target or preferences check.

## Validation checklist

1. Notification class uses Laravel Notifications; channels correct.
2. Dispatched on the Redis queue with retry config.
3. Credentials in env/GitHub secrets only.
4. Optional channels respect user preferences.
5. Failure handling logs + retries; no infinite retry.
6. Tests assert the queued job and payload (see `skills/testing`).

## Cross-references

- `skills/security/SKILL.md` — secrets handling.
- `skills/announcements/SKILL.md` — targeted announcement delivery.
- `skills/audit-logging/SKILL.md` — notification failure logging.

## Agent behavior (mandatory everywhere)

1. Inspect the existing implementation before modifying it.
2. Follow existing project conventions already established.
3. Do not rewrite working code unnecessarily.
4. Do not introduce technologies outside the EduCore stack.
5. Do not create unnecessary abstractions.
6. Do not create duplicate business logic.
7. Do not invent database relationships.
8. Do not bypass authorization.
9. Do not hardcode secrets.
10. Do not modify unrelated modules.
11. Run appropriate tests after changes.
12. Explain important architectural decisions.