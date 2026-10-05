<script setup>
import { Archive, Megaphone, Paperclip, Pencil, Plus, Send, Trash2 } from '@lucide/vue'
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
const fileInput = ref(null)
const existingAttachments = ref([])
const removeAttachmentIds = ref([])
const blank = () => ({ title: '', body: '', announcement_type: 'general', audience_type: audienceOptions.value[0]?.value ?? 'section', audience_id: '', publish: false, attachments: [] })
const form = useForm(blank())
const targetOptions = computed(() => (props.targets[form.audience_type] ?? []).map((t) => ({ value: t.id, label: t.label })))
const needsTarget = computed(() => !AUDIENCES.find((a) => a.value === form.audience_type)?.group)
watch(() => form.audience_type, () => { if (!editing.value) form.audience_id = '' })

const openCreate = () => {
  editing.value = null
  existingAttachments.value = []
  removeAttachmentIds.value = []
  if (fileInput.value) fileInput.value.value = ''
  form.defaults(blank()).reset()
  form.clearErrors()
  showForm.value = true
}
const openEdit = (item) => {
  editing.value = item
  existingAttachments.value = [...(item.attachments ?? [])]
  removeAttachmentIds.value = []
  if (fileInput.value) fileInput.value.value = ''
  form.defaults({ title: item.title, body: item.body, announcement_type: item.announcement_type ?? 'general', audience_type: item.audience_type, audience_id: item.audience_id ?? '', publish: false, attachments: [] }).reset()
  form.clearErrors()
  showForm.value = true
}
const removeExistingAttachment = (id) => {
  removeAttachmentIds.value.push(id)
  existingAttachments.value = existingAttachments.value.filter((a) => a.id !== id)
}
const save = (publish) => {
  form.publish = publish
  const options = {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => (showForm.value = false),
  }
  if (editing.value) {
    form.transform((data) => ({
      ...data,
      _method: 'PUT',
      audience_id: needsTarget.value ? data.audience_id || null : null,
      remove_attachment_ids: removeAttachmentIds.value,
    })).post(`/announcements/${editing.value.id}`, options)
  } else {
    form.transform((data) => ({
      ...data,
      audience_id: needsTarget.value ? data.audience_id || null : null,
    })).post('/announcements', options)
  }
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
    <PageHeader eyebrow="Communication" title="Manage announcements" :description="canTargetGroups ? 'Write to everyone, a role group or one department, program, section or course.' : 'Write to the sections and courses you teach.'">
      <template #actions>
        <IconButton :icon="Megaphone" href="/announcements" size="md" label="View feed" />
        <IconButton :icon="Plus" size="md" variant="primary" label="New announcement" :disabled="!audienceOptions.length" @click="openCreate" />
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
              <div v-if="item.attachments?.length" class="mt-2 flex flex-wrap gap-1.5">
                <a
                  v-for="att in item.attachments"
                  :key="att.id"
                  :href="`/announcements/${item.id}/attachments/${att.id}/download`"
                  class="inline-flex items-center gap-1 rounded bg-muted-light/70 px-2 py-0.5 text-caption font-medium text-muted hover:text-primary dark:bg-dark-muted/20 dark:text-dark-muted"
                >
                  <Paperclip class="size-3" />
                  {{ att.original_name }}
                </a>
              </div>
            </div>
            <div class="flex flex-wrap gap-1">
              <template v-if="item.publish_state === 'draft'">
                <IconButton :icon="Send" variant="success" label="Publish announcement" @click="publish(item)" />
                <IconButton :icon="Pencil" label="Edit draft" @click="openEdit(item)" />
                <IconButton :icon="Trash2" variant="danger" label="Delete draft" @click="remove(item)" />
              </template>
              <IconButton v-else-if="item.publish_state === 'published'" :icon="Archive" label="Archive announcement" @click="archive(item)" />
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
        <div class="space-y-2 border-t border-border-default pt-3 dark:border-dark-border">
          <label class="block text-small font-medium text-ink dark:text-dark-ink">Attachments</label>
          <div v-if="existingAttachments.length" class="space-y-1">
            <p class="text-caption text-muted dark:text-dark-muted">Attached files:</p>
            <div v-for="att in existingAttachments" :key="att.id" class="flex items-center justify-between rounded-md bg-muted-light/60 px-3 py-1.5 text-small dark:bg-dark-muted/20">
              <span class="truncate">{{ att.original_name }} ({{ Math.round(att.size / 1024) }} KB)</span>
              <button type="button" class="text-error hover:underline text-caption ml-2 cursor-pointer" @click="removeExistingAttachment(att.id)">Remove</button>
            </div>
          </div>
          <input
            ref="fileInput"
            type="file"
            multiple
            accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx,.txt,.zip"
            class="block w-full text-small text-muted file:mr-3 file:min-h-9 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:text-small file:font-semibold file:text-primary dark:text-dark-muted dark:file:bg-dark-primary/15 dark:file:text-dark-primary"
            @change="(e) => (form.attachments = Array.from(e.target.files ?? []))"
          />
          <p class="text-caption text-muted dark:text-dark-muted">PDF, PNG, JPG, DOCX, XLSX, ZIP · max 10 MB per file</p>
          <p v-if="form.errors.attachments" class="text-small text-error" role="alert">{{ form.errors.attachments }}</p>
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
