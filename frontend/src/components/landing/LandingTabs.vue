<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

// Tabs with WAI-ARIA keyboard support and automatic activation. Panels use the
// ids `${idPrefix}-panel-${key}`. Underline tabs (UI-COMPONENTS §8) by default;
// `vertical` lays them out as a side list from `lg` (each tab can carry a
// `hint` line) and as a two-column grid of buttons below it, so no tab is
// ever scrolled out of sight.
const props = defineProps({
  tabs: { type: Array, required: true }, // [{ key, label, short?, icon?, hint? }] — `short` replaces the label on phones
  idPrefix: { type: String, required: true },
  label: { type: String, required: true },
  dark: { type: Boolean, default: false },
  vertical: { type: Boolean, default: false },
})

const active = defineModel({ type: String, required: true })
const buttons = ref([])

// A vertical list is only a column from lg; its arrow keys follow the layout.
const wide = ref(false)
let query = null
const sync = () => {
  wide.value = query.matches
}
onMounted(() => {
  if (!props.vertical) return
  query = window.matchMedia('(min-width: 1024px)')
  sync()
  query.addEventListener('change', sync)
})
onBeforeUnmount(() => query?.removeEventListener('change', sync))

const orientation = computed(() => (props.vertical && wide.value ? 'vertical' : 'horizontal'))

const onKeydown = async (event, index) => {
  const last = props.tabs.length - 1
  const [prev, next] = orientation.value === 'vertical' ? ['ArrowUp', 'ArrowDown'] : ['ArrowLeft', 'ArrowRight']
  const target = { [next]: index === last ? 0 : index + 1, [prev]: index === 0 ? last : index - 1, Home: 0, End: last }[event.key]
  if (target === undefined) return
  event.preventDefault()
  active.value = props.tabs[target].key
  await nextTick()
  buttons.value[target]?.focus()
}

const focus = computed(() => (props.dark ? 'focus-visible:outline-dark-primary' : 'focus-visible:outline-primary dark:focus-visible:outline-dark-primary'))

const listClass = computed(() => {
  if (props.vertical) return 'grid grid-cols-2 gap-2 lg:flex lg:flex-col lg:gap-1 lg:border-l lg:border-border-default dark:lg:border-dark-border'
  return ['no-scrollbar flex max-w-full overflow-x-auto border-b', props.dark ? 'border-dark-border' : 'border-border-default dark:border-dark-border']
})

const buttonClass = (selected) => {
  if (props.vertical) {
    return [
      'relative flex min-h-11 items-center gap-2 rounded-md border px-3 py-2 text-left text-label transition-colors duration-150 focus-visible:outline-2 focus-visible:-outline-offset-2',
      'lg:w-full lg:items-start lg:gap-3 lg:rounded-l-none lg:border-0 lg:px-4 lg:py-3',
      selected
        ? 'border-primary/30 bg-primary/5 font-semibold text-primary lg:bg-background dark:border-dark-primary/40 dark:bg-dark-primary/10 dark:text-dark-primary dark:lg:bg-dark-bg'
        : 'border-border-default text-muted hover:text-ink lg:hover:bg-background dark:border-dark-border dark:text-dark-muted dark:hover:text-dark-ink dark:lg:hover:bg-dark-bg',
    ]
  }
  const tone = props.dark
    ? selected ? 'font-semibold text-dark-ink' : 'text-dark-muted hover:text-dark-ink'
    : selected ? 'font-semibold text-primary dark:text-dark-primary' : 'text-muted hover:text-ink dark:text-dark-muted dark:hover:text-dark-ink'
  return ['relative flex min-h-11 shrink-0 items-center gap-2 px-4 text-label transition-colors duration-150 focus-visible:outline-2 focus-visible:-outline-offset-2', tone]
}

const indicatorClass = (selected) => {
  if (props.vertical) return ['absolute inset-y-0 -left-px hidden w-0.5 bg-primary transition-transform duration-200 ease-out lg:block dark:bg-dark-primary', selected ? 'scale-y-100' : 'scale-y-0']
  return ['absolute inset-x-0 bottom-0 h-0.5 transition-transform duration-200 ease-out', props.dark ? 'bg-dark-primary' : 'bg-primary dark:bg-dark-primary', selected ? 'scale-x-100' : 'scale-x-0']
}
</script>

<template>
  <div :class="vertical ? '' : 'flex justify-center'">
    <div :class="listClass" role="tablist" :aria-label="label" :aria-orientation="orientation">
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
        :class="[buttonClass(active === tab.key), focus]"
        @click="active = tab.key"
        @keydown="onKeydown($event, i)"
      >
        <component :is="tab.icon" v-if="tab.icon" class="h-4 w-4 shrink-0" :class="vertical ? 'lg:mt-0.5 lg:h-5 lg:w-5' : ''" aria-hidden="true" />
        <span v-if="vertical" class="min-w-0">
          <span class="block">{{ tab.label }}</span>
          <span v-if="tab.hint" class="hidden text-caption font-normal text-muted lg:mt-1 lg:block dark:text-dark-muted">{{ tab.hint }}</span>
        </span>
        <template v-else-if="tab.short">
          <span class="sm:hidden">{{ tab.short }}</span>
          <span class="hidden sm:inline">{{ tab.label }}</span>
        </template>
        <template v-else>{{ tab.label }}</template>
        <span :class="indicatorClass(active === tab.key)" aria-hidden="true" />
      </button>
    </div>
  </div>
</template>
