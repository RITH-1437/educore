export function scrollToId(id, offset = 88) {
  const el = document.getElementById(id)
  if (!el) return

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  const top = el.getBoundingClientRect().top + window.scrollY - offset

  if (reduce) {
    window.scrollTo({ top, behavior: 'auto' })
    return
  }

  window.scrollTo({ top, behavior: 'smooth' })
}