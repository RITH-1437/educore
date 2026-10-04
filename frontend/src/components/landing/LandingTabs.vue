<script setup>
import { nextTick, ref } from 'vue'

// Underline tabs (UI-COMPONENTS §8) with WAI-ARIA keyboard support and
// automatic activation. Panels use the ids `${idPrefix}-panel-${key}`.
const props = defineProps({
  tabs: { type: Array, required: true }, // [{ key, label, icon? }]
  idPrefix: { type: String, required: true },
  label: { type: String, required: true },
  dark: { type: Boolean, default: false },
})

const active = defineModel({ type: String, required: true })
const buttons = ref([])

const onKeydown = async (event, index) => {
  const last = props.tabs.length - 1
  const next = { ArrowRight: index === last ? 0 : index + 1, ArrowLeft: index === 0 ? last : index - 1, Home: 0, End: last }[event.key]
  if (next === undefined) return
  event.preventDefault()
  active.value = props.tabs[next].key
  await nextTick()
  buttons.value[next]?.focus()
}

const tone = (selected) => {
  if (props.dark) return selected ? 'font-semibold text-dark-ink' : 'text-dark-muted hover:text-dark-ink'
  return selected ? 'font-semibold text-primary dark:text-dark-primary' : 'text-muted hover:text-ink dark:text-dark-muted dark:hover:text-dark-ink'
}
</script>

<template>
  <div class="flex justify-center">
    <div
      class="no-scrollbar flex max-w-full overflow-x-auto border-b"
      :class="dark ? 'border-dark-border' : 'border-border-default dark:border-dark-border'"
      role="tablist"
      :aria-label="label"
    >
      <button
        v-for="(tab, i) in tabs"
        :id="`${idPrefix}-tab-${tab.key}`"
        :key="tab.key"
        :ref="(el) => { buttons[i] = el }"
        type="button"
        role="tab"
        :aria-selected="active === tab.key"
        :aria-controls="`${idPrefix}-panel-${tab.key}`"
        :tabindex="active === tab.key ? 0 : -1"
        class="relative flex min-h-11 shrink-0 items-center gap-2 px-4 text-label transition-colors duration-150 focus-visible:outline-2 focus-visible:-outline-offset-2"
        :class="[tone(active === tab.key), dark ? 'focus-visible:outline-dark-primary' : 'focus-visible:outline-primary']"
        @click="active = tab.key"
        @keydown="onKeydown($event, i)"
      >
        <component :is="tab.icon" v-if="tab.icon" class="h-4 w-4" aria-hidden="true" />
        {{ tab.label }}
        <span
          class="absolute inset-x-0 bottom-0 h-0.5 transition-transform duration-200 ease-out"
          :class="[dark ? 'bg-dark-primary' : 'bg-primary dark:bg-dark-primary', active === tab.key ? 'scale-x-100' : 'scale-x-0']"
          aria-hidden="true"
        />
      </button>
    </div>
  </div>
</template>
