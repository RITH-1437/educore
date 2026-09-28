<script setup>
import { ref } from 'vue'

const props = defineProps({
  content: { type: String, required: true },
  placement: { type: String, default: 'top', validator: (value) => ['top', 'bottom', 'left', 'right'].includes(value) },
  delay: { type: Number, default: 200 },
})

const visible = ref(false)
let timer

const show = () => {
  clearTimeout(timer)
  timer = setTimeout(() => { visible.value = true }, props.delay)
}

const hide = () => {
  clearTimeout(timer)
  visible.value = false
}
</script>

<template>
  <span class="relative inline-flex" @mouseenter="show" @mouseleave="hide" @focusin="show" @focusout="hide">
    <slot />
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <span
        v-if="visible"
        role="tooltip"
        class="pointer-events-none absolute z-50 w-max max-w-64 rounded-md bg-primary-dark px-3 py-2 text-caption text-white shadow-md"
        :class="{
          'bottom-full left-1/2 mb-2 -translate-x-1/2': placement === 'top',
          'left-1/2 top-full mt-2 -translate-x-1/2': placement === 'bottom',
          'right-full top-1/2 mr-2 -translate-y-1/2': placement === 'left',
          'left-full top-1/2 ml-2 -translate-y-1/2': placement === 'right',
        }"
      >
        {{ content }}
      </span>
    </Transition>
  </span>
</template>
