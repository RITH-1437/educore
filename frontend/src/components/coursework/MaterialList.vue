<script setup>
import { Download, ExternalLink, Pencil, Trash2 } from '@lucide/vue'
import IconButton from '../IconButton.vue'
import { linkHost, materialIcon, materialSize, sharedOn } from '../../utils/materials'

// One list of course materials: a file downloads through the authorized route,
// a link opens in a new tab. Editors also get edit / remove.
defineProps({
  materials: { type: Array, required: true },
  canShare: { type: Boolean, default: false },
})
defineEmits(['edit', 'remove'])
</script>

<template>
  <ul class="divide-y divide-border-default dark:divide-dark-border">
    <li v-for="material in materials" :key="material.id" class="flex items-start gap-4 py-4 first:pt-0 last:pb-0">
      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
        <component :is="materialIcon(material)" class="h-5 w-5" aria-hidden="true" />
      </span>

      <div class="min-w-0 flex-1">
        <a
          v-if="material.kind === 'link'"
          :href="material.url"
          target="_blank"
          rel="noopener noreferrer"
          class="rounded-sm text-small font-semibold text-ink hover:text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:text-dark-ink dark:hover:text-dark-primary"
        >{{ material.title }}</a>
        <a
          v-else
          :href="`/materials/${material.id}/file`"
          class="rounded-sm text-small font-semibold text-ink hover:text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:text-dark-ink dark:hover:text-dark-primary"
        >{{ material.title }}</a>
        <p v-if="material.description" class="mt-1 whitespace-pre-line text-small text-muted dark:text-dark-muted">{{ material.description }}</p>
        <p class="mt-1 flex flex-wrap gap-x-1 text-caption text-muted dark:text-dark-muted">
          <span v-if="material.kind === 'link'">{{ linkHost(material.url) }}</span>
          <span v-else-if="material.file">{{ material.file.name }} · {{ materialSize(material.file.size) }}</span>
          <span v-if="material.shared_by">· {{ material.shared_by }}</span>
          <span>· {{ sharedOn(material.created_at) }}</span>
        </p>
      </div>

      <div class="flex shrink-0 items-center gap-1">
        <IconButton v-if="material.kind === 'link'" :icon="ExternalLink" :href="material.url" native new-tab :label="`Open ${material.title} in a new tab`" />
        <IconButton v-else :icon="Download" :href="`/materials/${material.id}/file`" native :label="`Download ${material.title}`" />
        <template v-if="canShare">
          <IconButton :icon="Pencil" :label="`Edit ${material.title}`" @click="$emit('edit', material)" />
          <IconButton :icon="Trash2" variant="danger" :label="`Remove ${material.title}`" @click="$emit('remove', material)" />
        </template>
      </div>
    </li>
  </ul>
</template>
