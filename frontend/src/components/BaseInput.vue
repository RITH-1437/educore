<script setup>
import { computed, useAttrs, useId } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  name: { type: String, default: '' },
  type: { type: String, default: 'text' },
  error: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  autofocus: { type: Boolean, default: false },
  autocomplete: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  variant: { type: String, default: 'solid' },
  required: { type: Boolean, default: false },
  id: { type: String, default: '' },
  hint: { type: String, default: '' },
})

defineEmits(['update:modelValue'])
defineOptions({ inheritAttrs: false })

// class/style belong to the wrapper (sizing, margins); everything else goes to
// the <input>. Otherwise a margin lands on the input and de-centers the icons.
const attrs = useAttrs()
const inputAttrs = computed(() => {
  const { class: _class, style: _style, ...rest } = attrs
  return rest
})

const generatedId = useId()
const inputId = computed(() => props.id || generatedId)
const errorId = computed(() => `${inputId.value}-error`)

const isGlass = computed(() => props.variant === 'glass')

const field = computed(() =>
  isGlass.value
    ? 'h-11 w-full rounded-md border border-white/25 bg-white/10 px-3 text-body text-white shadow-sm backdrop-blur-sm focus:bg-white/15 transition-colors duration-150 ease-out placeholder:text-white/60 hover:border-white/40 focus:border-dark-primary focus:outline-none focus:ring-2 focus:ring-dark-primary/40 disabled:cursor-not-allowed disabled:opacity-50'
    : 'h-11 w-full rounded-md border border-border-default bg-surface/70 backdrop-blur-sm px-3 text-body text-ink shadow-sm transition-colors duration-150 ease-out placeholder:text-muted hover:border-border-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-dark-border dark:bg-dark-surface/60 dark:text-dark-ink dark:placeholder:text-dark-muted dark:focus:border-dark-primary dark:focus:ring-dark-primary/20'
)

const errorField = computed(() =>
  isGlass.value
    ? 'border-error/80 bg-error/10 hover:border-error focus:border-error focus:ring-error/40'
    : 'border-error hover:border-error focus:border-error focus:ring-error/20'
)

const labelClass = computed(() =>
  isGlass.value
    ? 'mb-2 block text-label font-medium text-white'
    : 'mb-2 block text-label font-medium text-ink dark:text-dark-ink'
)

const leadingIcon = computed(() =>
  isGlass.value
    ? 'pointer-events-none absolute inset-y-0 left-3 flex items-center text-white/75 transition-colors duration-150 group-focus-within:text-dark-primary'
    : 'pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted transition-colors duration-150 group-focus-within:text-primary dark:text-dark-muted dark:group-focus-within:text-dark-primary'
)

const message = computed(() =>
  isGlass.value
    ? 'mt-2 flex items-center gap-1.5 text-small text-red-100'
    : 'mt-2 flex items-center gap-1.5 text-small text-error'
)
</script>

<template>
  <div :class="attrs.class" :style="attrs.style">
    <label v-if="label" :for="inputId" :class="labelClass">
      {{ label }}
      <span v-if="required" class="ml-1 text-error" aria-hidden="true">*</span>
    </label>

    <div class="group relative">
      <div v-if="$slots.leading" :class="leadingIcon">
        <slot name="leading" />
      </div>

      <input
        :id="inputId"
        :name="name || undefined"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :autofocus="autofocus"
        :autocomplete="autocomplete || undefined"
        :disabled="disabled"
        :required="required"
        :aria-required="required || undefined"
        :aria-invalid="error ? 'true' : 'false'"
        :aria-describedby="error ? errorId : undefined"
        :class="[field, error ? errorField : '', { 'pl-10': $slots.leading, 'pr-10': $slots.trailing }]"
        v-bind="inputAttrs"
        @input="$emit('update:modelValue', $event.target.value)"
      />

      <div v-if="$slots.trailing" class="absolute inset-y-0 right-0 flex items-center pr-2">
        <slot name="trailing" />
      </div>
    </div>

    <p v-if="hint && !error" class="mt-2 text-small text-muted dark:text-dark-muted">{{ hint }}</p>

    <p v-if="error" :id="errorId" :class="message" role="alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
      </svg>
      <span>{{ error }}</span>
    </p>
  </div>
</template>
