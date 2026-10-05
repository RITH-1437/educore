<script setup>
import { computed, ref } from 'vue'
import IconButton from '../../components/IconButton.vue'
import { Paperclip, SquarePen } from '@lucide/vue'
import { Head, usePage } from '@inertiajs/vue3'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import { typeLabel, typeVariant, when } from '../../utils/announcements'

const props = defineProps({
  announcements: { type: Object, required: true },
  canManage: { type: Boolean, default: false },
})

const page = usePage()
const isStudent = computed(() => page.props.auth?.user?.role?.slug === 'student')

const category = ref('all')
const categories = [
  { value: 'all', label: 'All news' },
  { value: 'academic', label: 'Academic' },
  { value: 'administrative', label: 'Administrative' },
  { value: 'event', label: 'Events' },
  { value: 'general', label: 'General' },
]

const filtered = computed(() => {
  if (category.value === 'all') return props.announcements.data
  return props.announcements.data.filter((item) => item.announcement_type === category.value)
})
</script>

<template>
  <Head title="Announcements - EduCore" />
  <div class="mx-auto max-w-3xl space-y-6">
    <PageHeader
      :eyebrow="isStudent ? 'Overview' : 'Communication'"
      title="Announcements"
      description="News addressed to you, your program, department, classes and courses."
    >
      <template v-if="canManage" #actions>
        <IconButton :icon="SquarePen" href="/announcements/manage" size="md" variant="primary" label="Write an announcement" />
      </template>
    </PageHeader>

    <!-- Category filter pills -->
    <div v-if="announcements.data.length" class="flex flex-wrap items-center gap-1.5" role="tablist" aria-label="Announcement categories">
      <button
        v-for="cat in categories"
        :key="cat.value"
        type="button"
        role="tab"
        :aria-selected="category === cat.value"
        class="inline-flex min-h-8 items-center rounded-pill px-3 text-caption font-medium transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-primary"
        :class="category === cat.value
          ? 'bg-primary text-white shadow-xs dark:bg-dark-primary dark:text-dark-bg'
          : 'bg-surface text-muted hover:bg-muted-light/60 hover:text-ink dark:bg-dark-surface dark:text-dark-muted dark:hover:bg-dark-muted/20 dark:hover:text-dark-ink border border-border-default dark:border-dark-border'"
        @click="category = cat.value"
      >
        {{ cat.label }}
      </button>
    </div>

    <BaseCard v-if="!filtered.length">
      <EmptyState
        title="No announcements"
        :description="category === 'all' ? 'Announcements for you will appear here.' : `No ${category} announcements found.`"
        :action-label="isStudent ? 'Open dashboard' : undefined"
        :action-href="isStudent ? '/dashboard' : undefined"
      />
    </BaseCard>

    <article v-for="item in filtered" :key="item.id">
      <BaseCard padding="lg">
        <div class="flex flex-wrap items-center gap-2">
          <BaseBadge :variant="typeVariant(item.announcement_type)" size="sm">{{ typeLabel(item.announcement_type) }}</BaseBadge>
          <span class="text-caption text-muted dark:text-dark-muted">{{ item.audience }} · {{ when(item.published_at) }}</span>
        </div>
        <h2 class="mt-3 text-h4 font-semibold text-ink dark:text-dark-ink">{{ item.title }}</h2>
        <p class="mt-2 whitespace-pre-line text-body text-ink dark:text-dark-ink">{{ item.body }}</p>
        <div v-if="item.attachments?.length" class="mt-3 flex flex-wrap gap-2 pt-2 border-t border-border-default dark:border-dark-border">
          <a
            v-for="att in item.attachments"
            :key="att.id"
            :href="`/announcements/${item.id}/attachments/${att.id}/download`"
            class="inline-flex items-center gap-1.5 rounded-md bg-muted-light/60 px-2.5 py-1 text-caption font-medium text-ink hover:bg-muted-light hover:text-primary dark:bg-dark-muted/20 dark:text-dark-ink dark:hover:bg-dark-muted/30"
          >
            <Paperclip class="size-3.5 text-muted dark:text-dark-muted" />
            <span>{{ att.original_name }}</span>
            <span class="text-caption text-muted dark:text-dark-muted">({{ Math.round(att.size / 1024) }} KB)</span>
          </a>
        </div>
        <p class="mt-4 text-caption text-muted dark:text-dark-muted">{{ item.author?.name }}</p>
      </BaseCard>
    </article>

    <Pagination v-if="category === 'all'" :links="announcements.meta?.links ?? []" />
  </div>
</template>
