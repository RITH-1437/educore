<script setup>
import { Pencil, Trash2 } from '@lucide/vue'
import IconButton from '../../components/IconButton.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'
import { AUDIENCES, stateBadge, typeLabel, typeVariant, when } from '../../utils/announcements'

const props = defineProps({
  announcements: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  targets: { type: Object, default: () => ({}) },
  canTargetGroups: { type: Boolean, default: false },
  types: { type: Array, default: () => [] },
})

const { confirm } = useConfirm()
const state = ref(props.filters.publish_state ?? '')
const stateOptions = [{ value: '', label: 'All' }, { value: 'draft', label: 'Drafts' }, { value: 'published', label: 'Published' }, { value: 'archived', label: 'Archived' }]
const applyFilter = () => router.get('/announcements/manage', { filters: state.value ? { publish_state: state.value } : undefined }, { preserveState: true, replace: true })

// Lecturers only see the audiences they may use (sections / courses they teach).
const audienceOptions = computed(() => AUDIENCES.filter((a) => (a.group ? props.canTargetGroups : (props.targets[a.value] ?? []).length > 0)))
const typeOptions = computed(() => props.types.map((t) => ({ value: t, label: typeLabel(t) })))

const editing = ref(null)
const showForm = ref(false)
const blank = () => ({ title: '', body: '', announcement_type: 'general', audience_type: audienceOptions.value[0]?.value ?? 'section', audience_id: '', publish: false })
const form = useForm(blank())
const targetOptions = computed(() => (props.targets[form.audience_type] ?? []).map((t) => ({ value: t.id, label: t.label })))
const needsTarget = computed(() => !AUDIENCES.find((a) => a.value === form.audience_type)?.group)
watch(() => form.audience_type, () => { if (!editing.value) form.audience_id = '' })

const openCreate = () => {
  editing.value = null
  form.defaults(blank()).reset()
  form.clearErrors()
  showForm.value = true
}
const openEdit = (item) => {
  editing.value = item
  form.defaults({ title: item.title, body: item.body, announcement_type: item.announcement_type ?? 'general', audience_type: item.audience_type, audience_id: item.audience_id ?? '', publish: false }).reset()
  form.clearErrors()
  showForm.value = true
}
const save = (publish) => {
  form.publish = publish
  const request = form.transform((data) => ({ ...data, audience_id: needsTarget.value ? data.audience_id || null : null }))
  const options = { preserveScroll: true, onSuccess: () => (showForm.value = false) }
  editing.value ? request.put(`/announcements/${editing.value.id}`, options) : request.post('/announcements', options)
}

const publish = async (item) => {
  if (await confirm({ title: 'Publish announcement?', message: `“${item.title}” will appear in the feed of: ${item.audience}. Published announcements cannot be edited.`, confirmLabel: 'Publish' })) {
    router.post(`/announcements/${item.id}/publish`, {}, { preserveScroll: true })
  }
}
const archive = async (item) => {
  if (await confirm({ title: 'Archive announcement?', message: 'It disappears from feeds; the record is kept.', confirmLabel: 'Archive', destructive: true })) {
    router.post(`/announcements/${item.id}/archive`, {}, { preserveScroll: true })
  }
}
const remove = async (item) => {
  if (await confirm({ title: 'Delete draft?', message: 'This cannot be undone.', confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/announcements/${item.id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <Head title="Manage announcements - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Communication" title="Manage announcements" :description="canTargetGroups ? 'Write to everyone, a role group or one faculty, department, program, section or course.' : 'Write to the sections and courses you teach.'">
      <template #actions>
        <BaseButton href="/announcements" variant="secondary">View feed</BaseButton>
        <BaseButton :disabled="!audienceOptions.length" @click="openCreate">New announcement</BaseButton>
      </template>
    </PageHeader>

    <div class="max-w-xs">
      <BaseSelect v-model="state" :options="stateOptions" label="Show" @update:model-value="applyFilter" />
    </div>

    <BaseCard v-if="!announcements.data.length">
      <EmptyState title="No announcements yet" description="Drafts and published announcements you manage appear here." />
    </BaseCard>

    <ul v-else class="space-y-3">
      <li v-for="item in announcements.data" :key="item.id">
        <BaseCard>
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <StatusBadge v-bind="stateBadge(item.publish_state)" />
                <BaseBadge :variant="typeVariant(item.announcement_type)" size="sm">{{ typeLabel(item.announcement_type) }}</BaseBadge>
              </div>
              <p class="mt-2 text-h4 font-semibold text-ink dark:text-dark-ink">{{ item.title }}</p>
              <p class="text-caption text-muted dark:text-dark-muted">
                {{ item.audience }} · {{ item.author?.name }} · {{ item.publish_state === 'draft' ? `edited ${when(item.updated_at)}` : `published ${when(item.published_at)}` }}
              </p>
              <p class="mt-2 line-clamp-2 text-small text-muted dark:text-dark-muted">{{ item.body }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <template v-if="item.publish_state === 'draft'">
                <BaseButton size="sm" @click="publish(item)">Publish</BaseButton>
                <IconButton :icon="Pencil" label="Edit draft" @click="openEdit(item)" />
                <IconButton :icon="Trash2" variant="danger" label="Delete draft" @click="remove(item)" />
              </template>
              <BaseButton v-else-if="item.publish_state === 'published'" size="sm" variant="ghost" @click="archive(item)">Archive</BaseButton>
            </div>
          </div>
        </BaseCard>
      </li>
    </ul>

    <Pagination :links="announcements.meta?.links ?? []" />

    <BaseModal v-model="showForm" :title="editing ? 'Edit draft' : 'New announcement'" size="lg">
      <form id="announcement-form" class="space-y-4" @submit.prevent="save(false)">
        <BaseInput v-model="form.title" name="title" label="Title" required :error="form.errors.title" />
        <BaseTextarea v-model="form.body" name="body" label="Message" required :rows="6" :error="form.errors.body" />
        <div class="grid gap-4 sm:grid-cols-3">
          <BaseSelect v-model="form.announcement_type" :options="typeOptions" label="Category" :error="form.errors.announcement_type" />
          <BaseSelect v-model="form.audience_type" :options="audienceOptions.map(({ value, label }) => ({ value, label }))" label="Audience" :error="form.errors.audience_type" />
          <BaseSelect v-if="needsTarget" v-model="form.audience_id" :options="targetOptions" label="Which one" placeholder="Choose…" :error="form.errors.audience_id" />
        </div>
      </form>
      <template #footer>
        <div class="flex flex-wrap justify-end gap-2">
          <BaseButton variant="ghost" @click="showForm = false">Cancel</BaseButton>
          <BaseButton type="submit" form="announcement-form" variant="secondary" :loading="form.processing && !form.publish">Save draft</BaseButton>
          <BaseButton v-if="!editing" :loading="form.processing && form.publish" @click="save(true)">Publish now</BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
