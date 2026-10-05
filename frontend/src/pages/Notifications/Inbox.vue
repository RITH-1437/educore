<script setup>
import { CheckCheck, Settings } from '@lucide/vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import IconButton from '../../components/IconButton.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import InboxItem from '../../components/notifications/InboxItem.vue'

const props = defineProps({
  notifications: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const unreadCount = computed(() => page.props.auth?.unread_notifications ?? 0)
const status = computed(() => props.filters.status ?? '')

const views = [
  { value: '', label: 'All', href: '/inbox' },
  { value: 'unread', label: 'Unread', href: '/inbox?filters[status]=unread' },
]

const markingAll = ref(false)
const markAllRead = () => router.post('/inbox/read-all', {}, {
  preserveScroll: true,
  onStart: () => { markingAll.value = true },
  onFinish: () => { markingAll.value = false },
})

const empty = computed(() => (status.value === 'unread'
  ? { title: 'You are all caught up', description: 'There are no unread notifications.' }
  : { title: 'No notifications yet', description: 'Updates about your registrations, grades, documents, invoices and announcements will appear here.' }))
</script>

<template>
  <Head title="Notifications - EduCore" />
  <div class="mx-auto max-w-3xl space-y-6">
    <PageHeader eyebrow="Account" title="Notifications" description="Everything EduCore told you by email or Telegram, kept here for 180 days.">
      <template #actions>
        <IconButton :icon="CheckCheck" size="md" label="Mark all as read" :disabled="unreadCount === 0" :loading="markingAll" @click="markAllRead" />
        <IconButton :icon="Settings" href="/notifications" size="md" label="Notification settings" />
      </template>
    </PageHeader>

    <nav aria-label="Filter notifications" class="flex gap-6 border-b border-border-default dark:border-dark-border">
      <Link
        v-for="view in views"
        :key="view.value"
        :href="view.href"
        preserve-scroll
        class="-mb-px flex min-h-11 items-center gap-2 border-b-2 text-small focus-visible:outline-2 focus-visible:outline-primary"
        :class="status === view.value ? 'border-primary font-semibold text-ink dark:border-dark-primary dark:text-dark-ink' : 'border-transparent text-muted hover:text-ink dark:text-dark-muted dark:hover:text-dark-ink'"
        :aria-current="status === view.value ? 'page' : undefined"
      >
        {{ view.label }}
        <span v-if="view.value === 'unread' && unreadCount" class="rounded-pill bg-primary/10 px-2 text-caption font-semibold text-primary dark:bg-dark-primary/15 dark:text-dark-primary">{{ unreadCount }}</span>
      </Link>
    </nav>

    <BaseCard v-if="!notifications.data.length">
      <EmptyState :title="empty.title" :description="empty.description" />
    </BaseCard>

    <BaseCard v-else padding="none">
      <ul class="divide-y divide-border-default dark:divide-dark-border">
        <InboxItem v-for="notification in notifications.data" :key="notification.id" :notification="notification" />
      </ul>
    </BaseCard>

    <Pagination :links="notifications.meta?.links ?? []" />
  </div>
</template>
