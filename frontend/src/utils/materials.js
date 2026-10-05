// Course materials (docs/44_Course-Materials-Report.md): icon and size helpers.
import { File, FileArchive, FileText, Image, Link2, Presentation, Sheet } from '@lucide/vue'

const BY_EXTENSION = { pdf: FileText, docx: FileText, txt: FileText, pptx: Presentation, xlsx: Sheet, zip: FileArchive, png: Image, jpg: Image, jpeg: Image }

export const materialIcon = (material) => {
  if (material.kind === 'link') return Link2
  const extension = material.file?.name?.split('.').pop()?.toLowerCase()
  return BY_EXTENSION[extension] ?? File
}

export const materialSize = (bytes) => {
  if (!bytes) return ''
  return bytes >= 1024 * 1024 ? `${(bytes / (1024 * 1024)).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`
}

export const sharedOn = (iso) => (iso ? new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }) : '')

// The host of a link, shown under its title ("example.edu").
export const linkHost = (url) => {
  try {
    return new URL(url).host
  } catch {
    return url
  }
}
