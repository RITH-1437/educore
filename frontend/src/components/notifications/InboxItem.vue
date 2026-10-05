<script setup>
import { Check } from '@lucide/vue'
import { Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import IconButton from '../IconButton.vue'
import { kindIcon, sentAgo } from '../../utils/notifications'

// One inbox message. Opening it (POST) marks it read and follows its link.
const props = defineProps({
  notification: { type: Object, required: true },
})

const unread = computed(() => !props.notification.read_at)
const marking = ref(false)
const markRead = () => router.post(`/inbox/${props.notification.id}/read`, {}, {
  preserveScroll: true,
  onStart: () => { marking.value = true },
  onFinish: () => { marking.value = false },
})
</script>

<template>
  <li class="flex items-start gap-4 px-5 py-4 first:rounded-t-xl last:rounded-b-xl" :class="unread ? 'bg-primary/5 dark:bg-dark-primary/10' : ''">
    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
      <component :is="kindIcon(notification.kind)" class="h-5 w-5" aria-hidden="true" />
    </span>

    <div class="min-w-0 flex-1">
      <Link
        :href="`/inbox/${notification.id}/open`"
        method="post"
        as="button"
        class="rounded-sm text-left text-small text-ink hover:text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:text-dark-ink dark:hover:text-dark-primary"
        :class="unread ? 'font-semibold' : 'font-medium'"
      >
        {{ notification.title }}
      </Link>
      <p class="mt-1 line-clamp-2 text-small text-muted dark:text-dark-muted">{{ notification.body }}</p>
      <p class="mt-1 flex items-center gap-2 text-caption text-muted dark:text-dark-muted">
        <span v-if="unread" class="inline-flex items-center gap-1.5 font-semibold text-primary dark:text-dark-primary">
          <span class="h-2 w-2 rounded-pill bg-primary dark:bg-dark-primary" aria-hidden="true" />New
        </span>
        <time :datetime="notification.created_at">{{ sentAgo(notification.created_at) }}</time>
      </p>
    </div>

    <IconButton v-if="unread" :icon="Check" :loading="marking" label="Mark as read" variant="success" @click="markRead" />
  </li>
</template>
