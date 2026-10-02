<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import PageHeader from '../../components/PageHeader.vue'

const props = defineProps({
  preferences: { type: Object, required: true },
})

const form = useForm({
  notify_by_email: props.preferences.notify_by_email,
  notify_by_telegram: props.preferences.notify_by_telegram && !!props.preferences.telegram_chat_id,
  telegram_chat_id: props.preferences.telegram_chat_id ?? '',
})

const save = () => form.transform((data) => ({ ...data, telegram_chat_id: data.telegram_chat_id || null })).put('/notifications', { preserveScroll: true })
const sendTest = () => router.post('/notifications/test', {}, { preserveScroll: true })

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
          <BaseInput
            v-model="form.telegram_chat_id"
            name="telegram_chat_id"
            label="Telegram chat id"
            inputmode="numeric"
            placeholder="e.g. 123456789"
            :error="form.errors.telegram_chat_id"
          />
          <p class="text-caption text-muted dark:text-dark-muted">Look up your numeric chat id (for example with Telegram's @userinfobot), then press Start in a chat with the university's bot — bots can only message people who started them.</p>
          <label class="flex items-start gap-3 text-small text-ink dark:text-dark-ink">
            <input v-model="form.notify_by_telegram" type="checkbox" :class="toggleClass" :disabled="!form.telegram_chat_id" />
            <span>
              <span class="font-medium">Send me Telegram notifications</span>
              <span class="block text-muted dark:text-dark-muted">Needs a chat id.</span>
            </span>
          </label>
        </div>
      </BaseCard>

      <div class="flex flex-wrap justify-between gap-3">
        <BaseButton variant="secondary" :disabled="form.isDirty" @click="sendTest">Send me a test</BaseButton>
        <BaseButton type="submit" :loading="form.processing" :disabled="!form.isDirty">Save settings</BaseButton>
      </div>
    </form>
  </div>
</template>
