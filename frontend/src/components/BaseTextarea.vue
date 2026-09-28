<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  error: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  rows: { type: Number, default: 4 },
  maxLength: { type: Number, default: undefined },
  id: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])
const generatedId = useId()
const textareaId = computed(() => props.id || generatedId)
const errorId = computed(() => `${textareaId.value}-error`)
const fieldClasses = computed(() => [
  'min-h-28 w-full resize-y rounded-md border bg-surface px-3 py-2 text-body text-ink shadow-sm transition-colors',
  'placeholder:text-muted focus-visible:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
  'disabled:cursor-not-allowed disabled:opacity-50 dark:bg-dark-surface dark:text-dark-ink dark:placeholder:text-dark-muted dark:border-dark-border',
  props.error ? 'border-error' : 'border-border-default',
])
</script>

<template>
  <div class="w-full">
    <label v-if="label" :for="textareaId" class="mb-2 block text-label font-medium text-ink dark:text-dark-ink">
      {{ label }}<span v-if="required" class="ml-1 text-error" aria-hidden="true">*</span>
    </label>
    <textarea
      :id="textareaId"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :rows="rows"
      :maxlength="maxLength"
      :aria-required="required || undefined"
      :aria-invalid="error ? 'true' : 'false'"
      :aria-describedby="error ? errorId : undefined"
      :class="fieldClasses"
      @input="emit('update:modelValue', $event.target.value)"
    />
    <div class="mt-1 flex justify-between gap-4">
      <p v-if="error" :id="errorId" class="text-small text-error" role="alert">{{ error }}</p>
      <p v-else-if="$slots.hint" class="text-small text-muted dark:text-dark-muted"><slot name="hint" /></p>
      <span v-if="maxLength" class="ml-auto text-caption text-muted dark:text-dark-muted">
        {{ String(modelValue ?? '').length }} / {{ maxLength }}
      </span>
    </div>
  </div>
</template>
