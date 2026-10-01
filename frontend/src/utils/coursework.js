// Due dates arrive as ISO strings (server time); shown in the viewer's locale.
export const formatDue = (iso) =>
  iso ? new Date(iso).toLocaleString(undefined, { weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—'

// ISO → value for <input type="datetime-local"> in local time.
export const toLocalInput = (iso) => {
  if (!iso) return ''
  const date = new Date(iso)
  return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16)
}
