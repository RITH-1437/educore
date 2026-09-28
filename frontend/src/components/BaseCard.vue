<script setup>
import { Link } from '@inertiajs/vue3'
import { useAttrs } from 'vue'

defineProps({
  padding: { type: String, default: 'md', validator: (value) => ['sm', 'md', 'lg'].includes(value) },
  hoverable: { type: Boolean, default: false },
  bordered: { type: Boolean, default: true },
  href: { type: String, default: '' },
  as: { type: String, default: 'section' },
  title: { type: String, default: '' },
})

defineOptions({ inheritAttrs: false })
const attrs = useAttrs()

const paddings = { sm: 'p-4', md: 'p-5', lg: 'p-6' }
</script>

<template>
  <Link
    v-if="href"
    :href="href"
    :class="[
      'block rounded-xl bg-surface shadow-sm transition duration-200 ease-out dark:bg-dark-surface motion-reduce:transition-none',
      paddings[padding],
      bordered ? 'border border-border-default dark:border-dark-border' : '',
      hoverable ? 'hover:-translate-y-0.5 hover:shadow-md hover:border-border-muted dark:hover:border-dark-muted' : '',
      attrs.class,
    ]"
  >
    <slot />
  </Link>
  <component
    v-else
    :is="as"
    :class="[
      'rounded-xl bg-surface shadow-sm dark:bg-dark-surface motion-safe:animate-fade-in',
      paddings[padding],
      bordered ? 'border border-border-default dark:border-dark-border' : '',
      hoverable ? 'transition duration-200 ease-out hover:-translate-y-0.5 hover:shadow-md hover:border-border-muted dark:hover:border-dark-muted motion-reduce:transition-none' : '',
      attrs.class,
    ]"
  >
    <header v-if="$slots.header || title || $slots.actions" class="mb-4 flex items-start justify-between gap-4">
      <div class="min-w-0">
        <slot name="header">
          <h2 v-if="title" class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ title }}</h2>
          <p v-if="$slots.description" class="mt-1 text-small text-muted dark:text-dark-muted"><slot name="description" /></p>
        </slot>
      </div>
      <slot name="actions" />
    </header>
    <slot />
    <footer v-if="$slots.footer" class="mt-5 border-t border-border-default pt-4 dark:border-dark-border">
      <slot name="footer" />
    </footer>
  </component>
</template>
