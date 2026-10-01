import { reactive } from 'vue'

// Single shared dialog state, rendered once by <ConfirmDialog> in the layout.
export const confirmState = reactive({
  open: false,
  title: '',
  message: '',
  confirmLabel: 'Confirm',
  cancelLabel: 'Cancel',
  destructive: false,
})

let resolver = null

/** Resolve the pending confirmation (called by <ConfirmDialog>). */
export function settleConfirm(result) {
  confirmState.open = false
  resolver?.(result)
  resolver = null
}

/**
 * Promise-based replacement for window.confirm().
 *
 *   if (await confirm({ title: 'Delete user?', message: '…', destructive: true })) { … }
 */
export function useConfirm() {
  const confirm = (options = {}) => {
    // A new request cancels any dialog that is still pending.
    resolver?.(false)

    Object.assign(confirmState, {
      title: 'Are you sure?',
      message: '',
      confirmLabel: 'Confirm',
      cancelLabel: 'Cancel',
      destructive: false,
      ...options,
      open: true,
    })

    return new Promise((resolve) => {
      resolver = resolve
    })
  }

  return { confirm }
}
