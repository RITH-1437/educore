<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { ExternalLink, MapPin } from '@lucide/vue'
import BaseModal from './BaseModal.vue'
import BaseTooltip from './BaseTooltip.vue'
import IconButton from './IconButton.vue'

// Campus map on every page (report 49): a tab fixed to the middle of the right
// edge opens a Google Maps preview of the shared `campus.map` place
// (`CAMPUS_MAP_*`). The map is requested only once the dialog opens.
const page = usePage()
const place = computed(() => page.props.campus?.map ?? {})
const open = ref(false)

// The name is searched at the coordinates, so the pin lands on that place and
// Google shows its card; either one alone still pins something sensible.
const embedUrl = computed(() => {
  const { name, coordinates } = place.value
  const params = new URLSearchParams({ q: name || coordinates, z: '17', output: 'embed' })
  if (name && coordinates) params.set('ll', coordinates)
  return `https://www.google.com/maps?${params}`
})
const mapsUrl = computed(() => place.value.url || `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(place.value.coordinates || place.value.name)}`)
</script>

<template>
  <template v-if="place.name || place.coordinates">
    <div class="fixed right-0 top-1/2 z-30 -translate-y-1/2">
      <BaseTooltip content="Campus map" placement="left">
        <button
          type="button"
          class="flex h-12 w-10 items-center justify-center rounded-l-lg bg-primary text-white shadow-md transition-colors hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none dark:bg-dark-primary dark:text-dark-bg dark:hover:bg-dark-primary/90"
          aria-label="Campus map"
          aria-haspopup="dialog"
          @click="open = true"
        >
          <MapPin class="h-5 w-5" aria-hidden="true" />
        </button>
      </BaseTooltip>
    </div>

    <BaseModal v-model="open" title="Campus map" size="xl">
      <div class="space-y-4">
        <iframe
          :src="embedUrl"
          :title="`Map of ${place.name || place.coordinates}`"
          class="h-80 w-full rounded-lg border border-border-default sm:h-96 dark:border-dark-border"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          allowfullscreen
        />
        <div class="flex items-center justify-between gap-3">
          <div class="flex min-w-0 items-start gap-2">
            <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-primary dark:text-dark-primary" aria-hidden="true" />
            <div class="min-w-0">
              <p class="text-small font-semibold text-ink dark:text-dark-ink">{{ place.name || place.coordinates }}</p>
              <p v-if="place.address" class="text-caption text-muted dark:text-dark-muted">{{ place.address }}</p>
            </div>
          </div>
          <IconButton :icon="ExternalLink" :href="mapsUrl" native new-tab label="Open in Google Maps" />
        </div>
      </div>
    </BaseModal>
  </template>
</template>
