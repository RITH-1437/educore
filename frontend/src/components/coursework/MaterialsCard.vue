<script setup>
import { FileUp, Link2, Plus } from '@lucide/vue'
import { router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../BaseButton.vue'
import BaseCard from '../BaseCard.vue'
import BaseInput from '../BaseInput.vue'
import BaseModal from '../BaseModal.vue'
import BaseTextarea from '../BaseTextarea.vue'
import EmptyState from '../EmptyState.vue'
import IconButton from '../IconButton.vue'
import MaterialList from './MaterialList.vue'
import { useConfirm } from '../../composables/useConfirm'

// Course materials of one section (report 44): everyone who can open the
// section reads them; its lecturers and managers share, edit and remove.
const props = defineProps({
  sectionId: { type: Number, required: true },
  materials: { type: Array, required: true },
  canShare: { type: Boolean, default: false },
  acceptedTypes: { type: Array, default: () => [] },
  maxKb: { type: Number, default: 20480 },
})

const { confirm } = useConfirm()
const showForm = ref(false)
const editing = ref(null)
const fileInput = ref(null)
const form = useForm({ title: '', description: '', kind: 'file', url: '', file: null })
const accept = computed(() => props.acceptedTypes.map((type) => `.${type}`).join(','))
const maxMb = computed(() => Math.round(props.maxKb / 1024))

const openShare = () => {
  editing.value = null
  form.reset()
  form.clearErrors()
  showForm.value = true
}
const openEdit = (material) => {
  editing.value = material
  form.clearErrors()
  form.title = material.title
  form.description = material.description ?? ''
  form.kind = material.kind
  form.url = material.url ?? ''
  form.file = null
  showForm.value = true
}
const pick = (event) => {
  form.clearErrors('file')
  form.file = event.target.files?.[0] ?? null
}
const close = () => {
  showForm.value = false
  if (fileInput.value) fileInput.value.value = ''
}

const save = () => {
  const options = { preserveScroll: true, onSuccess: close }
  if (editing.value) {
    form.transform(({ title, description, url }) => ({ title, description, ...(editing.value.kind === 'link' ? { url } : {}) }))
      .put(`/materials/${editing.value.id}`, options)
    return
  }
  form.transform(({ title, description, kind, url, file }) => ({ title, description, kind, ...(kind === 'link' ? { url } : { file }) }))
    .post(`/coursework/sections/${props.sectionId}/materials`, { ...options, forceFormData: true })
}

const remove = async (material) => {
  if (await confirm({ title: 'Remove material?', message: `“${material.title}” will no longer be available to students${material.kind === 'file' ? ' and its file is deleted' : ''}.`, confirmLabel: 'Remove', destructive: true })) {
    router.delete(`/materials/${material.id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <BaseCard padding="lg" title="Course materials">
    <template #description>Handouts, slides and links for this section; students in the section are told when one is added.</template>
    <template v-if="canShare" #actions>
      <IconButton :icon="Plus" size="md" label="Share a material" @click="openShare" />
    </template>

    <EmptyState v-if="!materials.length" title="No materials yet" :description="canShare ? 'Share slides, readings or a link with the section.' : 'Your lecturers have not shared anything here yet.'" />
    <MaterialList v-else :materials="materials" :can-share="canShare" @edit="openEdit" @remove="remove" />

    <BaseModal v-model="showForm" :title="editing ? 'Edit material' : 'Share a material'" size="lg">
      <form class="space-y-5" @submit.prevent="save">
        <fieldset v-if="!editing" class="space-y-2">
          <legend class="text-label font-medium text-ink dark:text-dark-ink">Type</legend>
          <div class="grid grid-cols-2 gap-3" role="radiogroup">
            <label
              v-for="option in [{ value: 'file', label: 'File', hint: 'Slides, handouts, readings', icon: FileUp }, { value: 'link', label: 'Link', hint: 'A page, video or folder', icon: Link2 }]"
              :key="option.value"
              class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors focus-within:outline-2 focus-within:outline-primary"
              :class="form.kind === option.value ? 'border-primary bg-primary/5 dark:border-dark-primary dark:bg-dark-primary/10' : 'border-border-default hover:bg-background dark:border-dark-border dark:hover:bg-dark-surface-2'"
            >
              <input v-model="form.kind" type="radio" name="kind" :value="option.value" class="sr-only" />
              <component :is="option.icon" class="mt-0.5 h-5 w-5 shrink-0 text-primary dark:text-dark-primary" aria-hidden="true" />
              <span>
                <span class="block text-small font-semibold text-ink dark:text-dark-ink">{{ option.label }}</span>
                <span class="block text-caption text-muted dark:text-dark-muted">{{ option.hint }}</span>
              </span>
            </label>
          </div>
        </fieldset>

        <BaseInput v-model="form.title" name="title" label="Title" placeholder="Week 3 slides" :error="form.errors.title" required />
        <BaseTextarea v-model="form.description" name="description" label="Note (optional)" placeholder="What it is, or what to read before class" :error="form.errors.description" />

        <BaseInput v-if="form.kind === 'link'" v-model="form.url" name="url" type="url" label="Link" placeholder="https://" :error="form.errors.url" required />
        <div v-else-if="!editing" class="space-y-1">
          <label for="material-file" class="block text-label font-medium text-ink dark:text-dark-ink">File <span class="text-error" aria-hidden="true">*</span></label>
          <input
            id="material-file"
            ref="fileInput"
            type="file"
            :accept="accept"
            class="block w-full text-small text-muted file:mr-3 file:min-h-9 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:text-small file:font-semibold file:text-primary dark:text-dark-muted dark:file:bg-dark-primary/15 dark:file:text-dark-primary"
            @change="pick"
          />
          <p class="text-caption text-muted dark:text-dark-muted">{{ acceptedTypes.join(', ').toUpperCase() }} · up to {{ maxMb }} MB</p>
          <p v-if="form.errors.file" class="text-small text-error dark:text-red-300" role="alert">{{ form.errors.file }}</p>
        </div>
        <p v-else class="text-small text-muted dark:text-dark-muted">To change the file, remove this material and share the new one.</p>

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" :disabled="form.processing" @click="close">Cancel</BaseButton>
          <BaseButton type="submit" :loading="form.processing" :disabled="!editing && form.kind === 'file' && !form.file">{{ editing ? 'Save' : 'Share' }}</BaseButton>
        </div>
      </form>
    </BaseModal>
  </BaseCard>
</template>
