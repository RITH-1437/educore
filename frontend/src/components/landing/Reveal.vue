<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  as: { type: String, default: 'div' },
  from: { type: String, default: 'up' },
  delay: { type: Number, default: 0 },
  threshold: { type: Number, default: 0.12 },
})

const el = ref(null)
const visible = ref(false)
let observer = null

onMounted(() => {
  // Show content immediately when motion is reduced or observers are unavailable.
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  if (reduce || !('IntersectionObserver' in window)) {
    visible.value = true
    return
  }

  observer = new IntersectionObserver(
    ([entry]) => {
      if (entry.isIntersecting) {
        visible.value = true
        observer.disconnect()
      }
    },
    { threshold: props.threshold, rootMargin: '0px 0px -48px 0px' }
  )
  if (el.value) observer.observe(el.value)
})

onBeforeUnmount(() => observer?.disconnect())
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
