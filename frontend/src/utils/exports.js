// Build a CSV export URL from list filters (`docs/31_CSV-Exports-Report.md`).
// Empty values are dropped; a nested `filters` object becomes `filters[key]`.
export const exportUrl = (path, params = {}) => {
  const query = new URLSearchParams()
  for (const [key, value] of Object.entries(params)) {
    if (value && typeof value === 'object') {
      for (const [inner, v] of Object.entries(value)) {
        if (v !== null && v !== undefined && v !== '') query.append(`${key}[${inner}]`, v)
      }
    } else if (value !== null && value !== undefined && value !== '') {
      query.append(key, value)
    }
  }
  const qs = query.toString()
  return qs ? `${path}?${qs}` : path
}
