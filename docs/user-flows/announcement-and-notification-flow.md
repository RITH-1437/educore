# EduCore — Announcement & Notification Multi-Channel Flow

This document details the multi-channel communication infrastructure of EduCore, covering scheduled announcements, audience targeting, read receipts, the in-app notification inbox, email dispatches, and Telegram bot linking.

- **Status:** `[Implemented]`
- **Source Code Reference:** `AnnouncementsController.php`, `InboxController.php`, `NotificationPreferencesController.php`, `backend/app/Notifications/`
- **PlantUML Equivalent:** [`puml/announcement-and-notification-flow.puml`](puml/announcement-and-notification-flow.puml)

---

## 1. Announcement Authoring & Delivery Pipeline

```mermaid
flowchart TD
    Start([Author opens /announcements/manage]) --> SelectAudience[Select Audience Target:<br/>All Users, Students Only, Lecturers Only, Department Staff]
    SelectAudience --> InputContent[Enter Title, Body, Priority, Attachment File]
    InputContent --> ScheduleDecision{Publish Immediately or Schedule?}

    ScheduleDecision -- Schedule for Later --> SetDateTime[Select Publish Date & Time]
    SetDateTime --> SaveScheduled[Submit POST /announcements/schedule<br/>status: 'scheduled']
    SaveScheduled --> CronJob[Laravel Task Scheduler checks hourly]
    CronJob --> PublishTrigger[Trigger Announcement Publishing]

    ScheduleDecision -- Publish Immediately --> DirectPublish[Submit POST /announcements/publish]
    DirectPublish --> PublishTrigger

    PublishTrigger --> UpdateStatus[Set announcements.status = 'published'<br/>published_at = now()]
    UpdateStatus --> DispatchNotifications[Dispatch Multi-Channel Notification Job to Redis Queue]

    DispatchNotifications --> Channel1[In-App Notification Inbox:<br/>Insert unread rows into notifications table]
    DispatchNotifications --> Channel2[Email Notification:<br/>Queue SMTP dispatch for users with email alerts enabled]
    DispatchNotifications --> Channel3[Telegram Notification:<br/>Queue Telegram Bot API message for linked chat IDs]

    Channel1 --> ReadReceiptWorkflow[User views announcement on /announcements<br/>POST /announcements/{id}/read records user_id & read_at]
```

---

## 2. Telegram Bot Linking & Class Reminder Workflow

```mermaid
flowchart TD
    StartLink([User opens /notifications]) --> ClickTelegram[Click 'Link Telegram Account']
    ClickTelegram --> GenToken[Backend generates unique 6-digit Telegram Link Token]
    GenToken --> ShowBotLink[Display Bot Username @EduCoreBot and /start {token} command]
    
    ShowBotLink --> UserOpensTelegram[User opens Telegram & sends /start {token}]
    UserOpensTelegram --> WebhookReceived[EduCore receives Telegram Webhook POST /api/telegram/webhook]
    WebhookReceived --> MatchToken{Token valid and unexpired?}
    
    MatchToken -- No --> SendTgFail[Bot replies: 'Link code invalid or expired']
    MatchToken -- Yes --> StoreChatId[Update user record:<br/>telegram_chat_id = message.chat.id<br/>telegram_linked_at = now()]
    StoreChatId --> SendTgSuccess[Bot replies: 'EduCore account linked successfully!']
    SendTgSuccess --> LiveAlerts[User now receives daily timetable reminders & grade alerts on Telegram]
```
