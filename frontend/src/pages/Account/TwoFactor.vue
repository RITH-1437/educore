<script setup>
import { Copy, Download, RefreshCw, ShieldOff } from '@lucide/vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import IconButton from '../../components/IconButton.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useToast } from '../../composables/useToast'

// Two-factor sign-in for your own account (report 52).
const props = defineProps({
  enabled: { type: Boolean, default: false },
  confirmedAt: { type: String, default: null },
  setup: { type: Object, default: null },
  recoveryCodesLeft: { type: Number, default: 0 },
  // Only right after turning on or regenerating: shown once.
  recoveryCodes: { type: Array, default: null },
})

const toast = useToast()

// The server's SVG is shown as an image, never injected into the page.
const qrSrc = computed(() => (props.setup?.qr_svg ? `data:image/svg+xml;charset=utf-8,${encodeURIComponent(props.setup.qr_svg)}` : ''))
const since = computed(() => (props.confirmedAt ? new Date(props.confirmedAt).toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' }) : ''))

const starting = ref(false)
const start = () => router.post('/account/two-factor', {}, { preserveScroll: true, onStart: () => { starting.value = true }, onFinish: () => { starting.value = false } })

const confirmForm = useForm({ code: '' })
const confirm = () => confirmForm.post('/account/two-factor/confirm', { preserveScroll: true, onFinish: () => confirmForm.reset() })

// Password-protected actions: new recovery codes, turn off.
const action = ref(null)
const passwordForm = useForm({ current_password: '' })
const openAction = (name) => {
  action.value = name
  passwordForm.reset()
  passwordForm.clearErrors()
}
const showAction = computed({ get: () => action.value !== null, set: (open) => { if (!open) action.value = null } })
const submitAction = () => {
  const options = { preserveScroll: true, onSuccess: () => { action.value = null }, onFinish: () => passwordForm.reset() }
  if (action.value === 'regenerate') passwordForm.post('/account/two-factor/recovery-codes', options)
  else passwordForm.delete('/account/two-factor', options)
}

const codesText = computed(() => (props.recoveryCodes ?? []).join('\n'))
const copyCodes = async () => {
  try {
    await navigator.clipboard.writeText(codesText.value)
    toast.success('Recovery codes copied.')
  } catch {
    toast.error('Copy failed: select the codes and copy them by hand.')
  }
}
const downloadCodes = () => {
  const blob = new Blob([`EduCore two-factor recovery codes\nEach code works once.\n\n${codesText.value}\n`], { type: 'text/plain' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = 'educore-recovery-codes.txt'
  link.click()
  URL.revokeObjectURL(link.href)
}
</script>

<template>
  <Head title="Two-factor sign-in - EduCore" />
  <div class="mx-auto max-w-2xl space-y-6">
    <PageHeader eyebrow="Account" title="Two-factor sign-in" description="Signing in also needs a 6-digit code from an authenticator app on your phone (Google Authenticator, Microsoft Authenticator, Authy…), so a stolen password alone is not enough." />

    <!-- Recovery codes: shown once, right after they are created. -->
    <BaseCard v-if="recoveryCodes?.length" padding="lg" class="ring-1 ring-warning/40">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 class="text-h4 font-semibold text-ink dark:text-dark-ink">Save your recovery codes</h2>
          <p class="mt-1 text-small text-muted dark:text-dark-muted">If you lose your phone, each code signs you in once. They will not be shown again.</p>
        </div>
        <div class="flex gap-1">
          <IconButton :icon="Copy" label="Copy recovery codes" @click="copyCodes" />
          <IconButton :icon="Download" label="Download recovery codes" @click="downloadCodes" />
        </div>
      </div>
      <ul class="mt-4 grid grid-cols-2 gap-2 font-mono text-body text-ink dark:text-dark-ink" aria-label="Recovery codes">
        <li v-for="code in recoveryCodes" :key="code" class="rounded-md bg-muted-light/60 px-3 py-2 text-center tracking-wider dark:bg-dark-muted/20">{{ code }}</li>
      </ul>
    </BaseCard>

    <!-- On -->
    <BaseCard v-if="enabled" padding="lg">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-h4 font-semibold text-ink dark:text-dark-ink">Two-factor sign-in</h2>
            <StatusBadge status="active" label="On" />
          </div>
          <p class="mt-1 text-small text-muted dark:text-dark-muted">On since {{ since }} · {{ recoveryCodesLeft }} of 8 recovery codes left</p>
        </div>
        <div class="flex gap-1">
          <IconButton :icon="RefreshCw" label="New recovery codes" @click="openAction('regenerate')" />
          <IconButton :icon="ShieldOff" variant="danger" label="Turn off two-factor sign-in" @click="openAction('disable')" />
        </div>
      </div>
      <p v-if="recoveryCodesLeft <= 2" class="mt-3 text-small text-warning-dark dark:text-warning">Few recovery codes are left: create new ones and keep them somewhere safe.</p>
    </BaseCard>

    <!-- Setup in progress -->
    <BaseCard v-else-if="setup" padding="lg">
      <h2 class="text-h4 font-semibold text-ink dark:text-dark-ink">Set up your authenticator app</h2>
      <ol class="mt-3 list-decimal space-y-1 pl-5 text-small text-muted dark:text-dark-muted">
        <li>In your authenticator app, add an account and scan this QR code.</li>
        <li>Then enter the 6-digit code the app shows for EduCore.</li>
      </ol>
      <div class="mt-5 flex flex-col items-center gap-5 sm:flex-row sm:items-start">
        <img :src="qrSrc" alt="QR code for your authenticator app" width="192" height="192" class="h-48 w-48 shrink-0 rounded-lg bg-white p-2" />
        <div class="w-full min-w-0 space-y-4">
          <div>
            <p class="text-small font-medium text-ink dark:text-dark-ink">Can't scan it? Enter this key:</p>
            <p class="mt-1 break-all rounded-md bg-muted-light/60 px-3 py-2 font-mono text-small tracking-wider text-ink dark:bg-dark-muted/20 dark:text-dark-ink">{{ setup.secret }}</p>
          </div>
          <form class="space-y-3" @submit.prevent="confirm">
            <BaseInput v-model="confirmForm.code" name="code" label="Code from the app" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" maxlength="7" required :error="confirmForm.errors.code" />
            <div class="flex flex-wrap gap-2">
              <BaseButton type="submit" :loading="confirmForm.processing">Turn on</BaseButton>
              <BaseButton variant="ghost" :loading="starting" @click="start">New QR code</BaseButton>
            </div>
          </form>
        </div>
      </div>
    </BaseCard>

    <!-- Off -->
    <BaseCard v-else padding="lg">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-h4 font-semibold text-ink dark:text-dark-ink">Two-factor sign-in</h2>
            <StatusBadge status="inactive" label="Off" />
          </div>
          <p class="mt-1 text-small text-muted dark:text-dark-muted">Recommended for every account, and especially for administrators.</p>
        </div>
        <BaseButton :loading="starting" @click="start">Set up</BaseButton>
      </div>
    </BaseCard>

    <BaseModal v-model="showAction" :title="action === 'regenerate' ? 'New recovery codes' : 'Turn off two-factor sign-in?'">
      <form id="two-factor-action" class="space-y-4" @submit.prevent="submitAction">
        <p class="text-small text-muted dark:text-dark-muted">
          {{ action === 'regenerate' ? 'Your current recovery codes stop working and eight new ones are shown once.' : 'Signing in will need only your password again. You will receive an email about this change.' }}
        </p>
        <BaseInput v-model="passwordForm.current_password" name="current_password" label="Your password" type="password" autocomplete="current-password" required :error="passwordForm.errors.current_password" />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <BaseButton variant="ghost" @click="showAction = false">Cancel</BaseButton>
          <BaseButton type="submit" form="two-factor-action" :variant="action === 'disable' ? 'danger' : 'primary'" :loading="passwordForm.processing">
            {{ action === 'regenerate' ? 'Create new codes' : 'Turn off' }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
