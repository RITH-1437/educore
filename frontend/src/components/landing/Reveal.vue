<script setup>
import { ref } from 'vue'
import { useInView } from './useInView'

const props = defineProps({
  as: { type: String, default: 'div' },
  from: { type: String, default: 'up' },
  delay: { type: Number, default: 0 },
  threshold: { type: Number, default: 0.12 },
})

const el = ref(null)
const visible = useInView(el, { threshold: props.threshold })
</script>

<template>
  <component
    :is="props.as"
    ref="el"
    class="reveal"
    :class="[
      visible ? 'is-visible' : '',
      props.from === 'left' ? 'from-left' : props.from === 'right' ? 'from-right' : props.from === 'scale' ? 'from-scale' : '',
    ]"
    :style="{ transitionDelay: `${props.delay}ms` }"
  >
    <slot />
  </component>
</template>
