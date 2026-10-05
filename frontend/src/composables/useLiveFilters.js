import { onBeforeUnmount, ref, watch } from 'vue'

/**
 * Soft search for list filter bars: the list follows the filters as they
 * change, so there is no search button — typed text applies after a short
 * pause, picked options and dates at once, and Enter applies immediately.
 *
 * The update is quiet, not a page load: scroll position kept, no progress bar,
 * the page is not dimmed (the layout only dims visits that show progress), and
 * `searching` is true while results load — bind it to the search field's
 * `loading` spinner.
 *
 * `apply(options)` is the page's own visit; it must pass `options` on to
 * `router.get(url, query, { preserveState: true, replace: true, ...options })`.
 * Call `applyNow` from the form's submit and after setting filters in code
 * (clear, shortcut chips): a visit runs only when the values differ from the
 * last ones applied, so nothing is requested twice.
 *
 * @param {(options: object) => void} apply
 * @param {{ text?: import('vue').Ref[], choices?: import('vue').Ref[], delay?: number }} sources
 */
export function useLiveFilters(apply, { text = [], choices = [], delay = 300 } = {}) {
  const values = () => JSON.stringify([...text, ...choices].map((source) => source.value))
  let applied = values()
  let timer = null
  // Counted, so a request cancelled by a newer one cannot hide the spinner early.
  let inFlight = 0
  const searching = ref(false)

  const applyNow = () => {
    clearTimeout(timer)
    const current = values()
    if (current === applied) return
    applied = current
    apply({
      preserveScroll: true,
      showProgress: false,
      onStart: () => {
        inFlight += 1
        searching.value = true
      },
      onFinish: () => {
        inFlight = Math.max(0, inFlight - 1)
        searching.value = inFlight > 0
      },
    })
  }

  if (text.length) {
    watch(text, () => {
      clearTimeout(timer)
      timer = setTimeout(applyNow, delay)
    })
  }
  if (choices.length) watch(choices, applyNow)

  onBeforeUnmount(() => clearTimeout(timer))

  return { applyNow, searching }
}
