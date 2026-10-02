<script setup>
import { Head } from '@inertiajs/vue3'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import { typeLabel, typeVariant, when } from '../../utils/announcements'

defineProps({
  announcements: { type: Object, required: true },
  canManage: { type: Boolean, default: false },
})
</script>

<template>
  <Head title="Announcements - EduCore" />
  <div class="mx-auto max-w-3xl space-y-6">
    <PageHeader eyebrow="Communication" title="Announcements" description="News addressed to you, your program, department, faculty, classes and courses.">
      <template v-if="canManage" #actions>
        <BaseButton href="/announcements/manage">Write an announcement</BaseButton>
      </template>
    </PageHeader>

    <BaseCard v-if="!announcements.data.length">
      <EmptyState title="No announcements" description="Announcements for you will appear here." />
    </BaseCard>

    <article v-for="item in announcements.data" :key="item.id">
      <BaseCard padding="lg">
        <div class="flex flex-wrap items-center gap-2">
          <BaseBadge :variant="typeVariant(item.announcement_type)" size="sm">{{ typeLabel(item.announcement_type) }}</BaseBadge>
          <span class="text-caption text-muted dark:text-dark-muted">{{ item.audience }} · {{ when(item.published_at) }}</span>
        </div>
        <h2 class="mt-3 text-h4 font-semibold text-ink dark:text-dark-ink">{{ item.title }}</h2>
        <p class="mt-2 whitespace-pre-line text-body text-ink dark:text-dark-ink">{{ item.body }}</p>
        <p class="mt-4 text-caption text-muted dark:text-dark-muted">{{ item.author?.name }}</p>
      </BaseCard>
    </article>

    <Pagination :links="announcements.meta?.links ?? []" />
  </div>
</template>
