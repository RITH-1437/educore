<script setup>
import IconButton from '../../components/IconButton.vue'
import { BellRing, Send, Unlink } from '@lucide/vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import PageHeader from '../../components/PageHeader.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  preferences: { type: Object, required: true },
})

const { confirm } = useConfirm()
const form = useForm({
  notify_by_email: props.preferences.notify_by_email,
  notify_by_telegram: props.preferences.notify_by_telegram && !!props.preferences.telegram_chat_id,
  telegram_chat_id: props.preferences.telegram_chat_id ?? '',
  class_reminders: props.preferences.class_reminders ?? true,
})

// A saved chat: linked through the bot or typed in by hand.
const linked = computed(() => Boolean(props.preferences.telegram_chat_id))
const maskedChat = computed(() => (props.preferences.telegram_chat_id ? `••••${String(props.preferences.telegram_chat_id).slice(-4)}` : ''))
// Typing a chat id stays available: always where one-tap linking is not set up, on request otherwise.
const manual = ref(!props.preferences.telegram_linkable)
const hasChat = computed(() => Boolean(form.telegram_chat_id))

const save = () => form.transform((data) => ({ ...data, telegram_chat_id: data.telegram_chat_id || null })).put('/notifications', { preserveScroll: true })
const sendTest = () => router.post('/notifications/test', {}, { preserveScroll: true })
// Leaves for Telegram (t.me link); pressing Start in the bot links the chat.
const connecting = ref(false)
const connect = () => router.post('/notifications/telegram/link', {}, { onStart: () => { connecting.value = true }, onFinish: () => { connecting.value = false } })
const disconnect = async () => {
  if (await confirm({ title: 'Disconnect Telegram?', message: 'EduCore stops messaging this chat, including class reminders. You can connect again at any time.', confirmLabel: 'Disconnect', destructive: true })) {
    router.delete('/notifications/telegram', { preserveScroll: true })
  }
}

const toggleClass = 'mt-1 size-4 rounded border-border-default text-primary focus-visible:outline-2 focus-visible:outline-primary disabled:opacity-50'
</script>

<template>
  <Head title="Notification settings - EduCore" />
  <div class="mx-auto max-w-3xl space-y-6">
    <PageHeader eyebrow="Account" title="Notification settings" description="Choose how EduCore reaches you. Messages are sent in the background, so they may take a minute to arrive." />

    <form class="space-y-6" @submit.prevent="save">
      <BaseCard title="Email" padding="lg">
        <template #description>Sent to {{ preferences.email }}.</template>
        <label class="flex items-start gap-3 text-small text-ink dark:text-dark-ink">
          <input v-model="form.notify_by_email" type="checkbox" :class="toggleClass" />
          <span>
            <span class="font-medium">Optional emails</span>
            <span class="block text-muted dark:text-dark-muted">Announcements, published grades, registration confirmations and assignment reminders.</span>
          </span>
        </label>
        <p class="mt-4 rounded-md bg-surface px-4 py-3 text-caption text-muted dark:bg-dark-surface-2 dark:text-dark-muted">
          Document request updates and invoice / payment notices are always emailed — they cannot be turned off.
        </p>
      </BaseCard>

      <BaseCard title="Telegram" padding="lg">
        <template #description>
          <span v-if="preferences.telegram_enabled">Time-sensitive alerts — announcements, reminders and status updates — in your Telegram chat.</span>
          <span v-else>Telegram delivery is not configured on this server yet. You can still save your chat id.</span>
        </template>
        <div class="space-y-4">
          <!-- One-tap linking (report 48): the bot links the chat in which you press Start. -->
          <div v-if="preferences.telegram_linkable" class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-border-default px-4 py-3 dark:border-dark-border">
            <div class="text-small">
              <template v-if="linked">
                <span class="flex items-center gap-2 font-medium text-ink dark:text-dark-ink">Connected <BaseBadge variant="success" size="sm">Chat {{ maskedChat }}</BaseBadge></span>
                <span class="block text-muted dark:text-dark-muted">Send /stop to the bot, or disconnect here.</span>
              </template>
              <template v-else>
                <span class="font-medium text-ink dark:text-dark-ink">Not connected</span>
                <span class="block text-muted dark:text-dark-muted">Opens Telegram — press Start in the EduCore bot. The link works once, for 15 minutes.</span>
              </template>
            </div>
            <IconButton v-if="linked" :icon="Unlink" size="md" variant="danger" label="Disconnect Telegram" @click="disconnect" />
            <IconButton v-else :icon="Send" size="md" variant="primary" label="Connect Telegram" :loading="connecting" @click="connect" />
          </div>

          <button v-if="preferences.telegram_linkable && !manual" type="button" class="text-caption font-medium text-primary hover:underline focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-primary" @click="manual = true">
            Enter a chat id instead
          </button>
          <template v-if="manual">
            <BaseInput
              v-model="form.telegram_chat_id"
              name="telegram_chat_id"
              label="Telegram chat id"
              inputmode="numeric"
              placeholder="e.g. 123456789"
              :error="form.errors.telegram_chat_id"
            />
            <p class="text-caption text-muted dark:text-dark-muted">Look up your numeric chat id (for example with Telegram's @userinfobot), then press Start in a chat with the university's bot — bots can only message people who started them.</p>
          </template>

          <label class="flex items-start gap-3 text-small text-ink dark:text-dark-ink">
            <input v-model="form.notify_by_telegram" type="checkbox" :class="toggleClass" :disabled="!hasChat" />
            <span>
              <span class="font-medium">Send me Telegram notifications</span>
              <span class="block text-muted dark:text-dark-muted">{{ hasChat ? 'Announcements, reminders and status updates.' : (preferences.telegram_linkable ? 'Connect Telegram first.' : 'Enter your chat id first.') }}</span>
            </span>
          </label>
          <label class="flex items-start gap-3 text-small text-ink dark:text-dark-ink">
            <input v-model="form.class_reminders" type="checkbox" :class="toggleClass" :disabled="!hasChat || !form.notify_by_telegram" />
            <span>
              <span class="font-medium">Class reminders</span>
              <span class="block text-muted dark:text-dark-muted">A message about {{ preferences.class_reminder_minutes ?? 30 }} minutes before each of your classes, with the room. Telegram only.</span>
            </span>
          </label>
        </div>
      </BaseCard>

      <div class="flex flex-wrap justify-between gap-3">
        <IconButton :icon="BellRing" size="md" label="Send me a test notification" :disabled="form.isDirty" @click="sendTest" />
        <BaseButton type="submit" :loading="form.processing" :disabled="!form.isDirty">Save settings</BaseButton>
      </div>
    </form>
  </div>
</template>
