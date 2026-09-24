<script setup>
import { useId } from 'vue'

defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  type: { type: String, default: 'text' },
  error: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  autofocus: { type: Boolean, default: false },
  autocomplete: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])

const inputId = useId()
const errorId = useId()
</script>

<template>
  <div>
    <label
      :for="inputId"
      class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300"
    >
      {{ label }}
    </label>

    <div class="group relative">
      <div
        v-if="$slots.leading"
        class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-slate-500 transition-colors duration-150 group-focus-within:text-blue-600 dark:text-slate-400"
      >
        <slot name="leading" />
      </div>

      <input
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :autofocus="autofocus"
        :autocomplete="autocomplete || undefined"
        :disabled="disabled"
        :aria-invalid="error ? 'true' : 'false'"
        :aria-describedby="error ? errorId : undefined"
        class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3.5 text-slate-800 shadow-xs transition-colors duration-150 ease-out placeholder:text-slate-500 hover:border-slate-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50 dark:placeholder:text-slate-400 dark:hover:border-slate-600 dark:focus:border-blue-400 dark:focus:ring-blue-400/25"
        :class="{
          'pl-10': $slots.leading,
          'pr-10': $slots.trailing,
          'border-red-600 hover:border-red-600 focus:border-red-600 focus:ring-red-600/20 dark:border-red-500 dark:focus:border-red-400 dark:focus:ring-red-400/25': error,
        }"
        @input="$emit('update:modelValue', $event.target.value)"
      />

      <div v-if="$slots.trailing" class="absolute inset-y-0 right-0 flex items-center pr-2">
        <slot name="trailing" />
      </div>
    </div>

    <p
      v-if="error"
      :id="errorId"
      class="mt-1.5 flex items-center gap-1.5 text-sm text-red-600 dark:text-red-400"
      role="alert"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
      </svg>
      <span>{{ error }}</span>
    </p>
  </div>
</template>