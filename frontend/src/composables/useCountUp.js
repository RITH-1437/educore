import { onBeforeUnmount, onMounted, ref, unref } from 'vue'

/**
 * Animates an integer from 0 to `target` once on mount (500ms, ease-out).
 * Shows the final value immediately when reduced motion is requested or the
 * target is not a finite number.
 */
export function useCountUp(target, duration = 500) {
  const display = ref(unref(target))
  let frame = null

  onMounted(() => {
    const end = Number(unref(target))
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
    if (!Number.isFinite(end) || reduce || end === 0) return

    const start = performance.now()
    const tick = (now) => {
      const progress = Math.min((now - start) / duration, 1)
      display.value = Math.round(end * (1 - (1 - progress) ** 3))
      if (progress < 1) frame = requestAnimationFrame(tick)
    }
    display.value = 0
    frame = requestAnimationFrame(tick)
  })

  onBeforeUnmount(() => frame && cancelAnimationFrame(frame))

  return display
}
