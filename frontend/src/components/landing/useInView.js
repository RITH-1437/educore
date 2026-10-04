import { onBeforeUnmount, onMounted, ref } from 'vue'

export const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches

/**
 * Flip `visible` to true the first time `target` scrolls into view. Content is
 * shown immediately when motion is reduced or IntersectionObserver is missing,
 * so nothing ever stays hidden.
 */
export function useInView(target, { threshold = 0.12, rootMargin = '0px 0px -48px 0px' } = {}) {
  const visible = ref(false)
  let observer = null

  onMounted(() => {
    if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
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
      { threshold, rootMargin }
    )
    if (target.value) observer.observe(target.value)
  })

  onBeforeUnmount(() => observer?.disconnect())

  return visible
}
