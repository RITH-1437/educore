<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number, Array], default: '' },
  options: { type: Array, default: () => [] },
  label: { type: String, default: '' },
  placeholder: { type: String, default: 'Select an option' },
  error: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  multiple: { type: Boolean, default: false },
  labelKey: { type: String, default: 'label' },
  valueKey: { type: String, default: 'value' },
  id: { type: String, default: '' },
  size: { type: Number, default: undefined },
})

const emit = defineEmits(['update:modelValue', 'change'])
const generatedId = useId()
const selectId = computed(() => props.id || generatedId)
const errorId = computed(() => `${selectId.value}-error`)

const onChange = (event) => {
  if (props.multiple) {
    const values = Array.from(event.target.selectedOptions, (option) => {
      const original = props.options.find((item) => String(item[props.valueKey]) === option.value)
      return original ? original[props.valueKey] : option.value
    })
    emit('update:modelValue', values)
    emit('change', values)
    return
  }

  const selected = props.options.find((item) => String(item[props.valueKey]) === event.target.value)
  const value = selected ? selected[props.valueKey] : event.target.value
  emit('update:modelValue', value)
  emit('change', value)
}
</script>

<template>
  <div class="w-full">
    <label v-if="label" :for="selectId" class="mb-2 block text-label font-medium text-ink dark:text-dark-ink">
      {{ label }}<span v-if="required" class="ml-1 text-error" aria-hidden="true">*</span>
    </label>
    <select
      :id="selectId"
      :value="modelValue"
      :disabled="disabled"
      :required="required"
      :multiple="multiple"
      :size="multiple ? size : undefined"
      :aria-required="required || undefined"
      :aria-invalid="error ? 'true' : 'false'"
      :aria-describedby="error ? errorId : undefined"
      class="min-h-11 w-full rounded-md border border-border-default bg-surface px-3 py-2 text-body text-ink shadow-sm transition-colors focus-visible:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-not-allowed disabled:opacity-50 dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink"
      @change="onChange"
    >
      <option v-if="!multiple && placeholder" value="">{{ placeholder }}</option>
      <option v-for="option in options" :key="option[valueKey]" :value="option[valueKey]">
        {{ option[labelKey] }}
      </option>
    </select>
    <p v-if="error" :id="errorId" class="mt-2 text-small text-error" role="alert">{{ error }}</p>
    <p v-else-if="$slots.hint" class="mt-2 text-small text-muted dark:text-dark-muted"><slot name="hint" /></p>
  </div>
</template>
