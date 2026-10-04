import { onBeforeUnmount, ref } from 'vue'

const STORAGE_KEY = 'educore_theme'
const theme = ref('light')

const readStored = () => {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored === 'light' || stored === 'dark') return stored
  } catch {
    // storage unavailable — fall through to the system preference
  }
  return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

const apply = (value) => {
  document.documentElement.classList.toggle('dark', value === 'dark')
  document.documentElement.style.colorScheme = value
}

/**
 * Light/dark theme for the dashboard and the landing page (one shared
 * preference). The `dark` class is applied to <html> only while a component
 * using this composable is mounted, so other public pages (login) keep their
 * own styling.
 */
export function useTheme() {
  theme.value = readStored()
  apply(theme.value)

  const set = (value) => {
    theme.value = value
    apply(value)
    try {
      localStorage.setItem(STORAGE_KEY, value)
    } catch {
      // ignore — preference just won't persist
    }
  }

  const toggle = () => set(theme.value === 'dark' ? 'light' : 'dark')

  onBeforeUnmount(() => {
    document.documentElement.classList.remove('dark')
    document.documentElement.style.colorScheme = ''
  })

  return { theme, toggle }
}
