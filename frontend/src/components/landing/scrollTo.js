/**
 * Smooth-scroll to a section and move keyboard focus to it so the next Tab
 * continues from there (respects prefers-reduced-motion).
 */
export function scrollToId(id, offset = 88) {
  const el = document.getElementById(id)
  if (!el) return

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  const top = el.getBoundingClientRect().top + window.scrollY - offset

  window.scrollTo({ top, behavior: reduce ? 'auto' : 'smooth' })

  if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '-1')
  el.focus({ preventScroll: true })
  history.replaceState(null, '', id === 'top' ? window.location.pathname : `#${id}`)
}
