<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { KeyRound, Smartphone } from '@lucide/vue'
import { nextTick, ref } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'
import AuthShell from '../../components/auth/AuthShell.vue'

// Second step of sign-in when two-factor is on (report 52): a code from the
// authenticator app, or one of the saved recovery codes.
const useRecovery = ref(false)
const form = useForm({ code: '', recovery_code: '' })

const toggle = async () => {
  useRecovery.value = !useRecovery.value
  form.reset()
  form.clearErrors()
  await nextTick()
  document.querySelector(useRecovery.value ? 'input[name=recovery_code]' : 'input[name=code]')?.focus()
}

const submit = () => form
  .transform((data) => (useRecovery.value ? { recovery_code: data.recovery_code } : { code: data.code }))
  .post('/two-factor-challenge', { preserveScroll: true, onError: () => form.reset() })
</script>

<script>
import GuestLayout from '../../layouts/GuestLayout.vue'

export default { layout: GuestLayout }
</script>

<template>
  <Head title="Two-factor sign-in" />
  <AuthShell
    title="Two-factor sign-in"
    :subtitle="useRecovery ? 'Enter one of the recovery codes you saved when you turned on two-factor sign-in. Each code works once.' : 'Enter the 6-digit code from your authenticator app.'"
  >
    <form class="mt-8" @submit.prevent="submit">
      <BaseInput
        v-if="!useRecovery"
        v-model="form.code"
        name="code"
        label="Authentication code"
        inputmode="numeric"
        autocomplete="one-time-code"
        placeholder="123456"
        maxlength="7"
        autofocus
        required
        variant="glass"
        :error="form.errors.code"
      >
        <template #leading><Smartphone class="h-5 w-5" aria-hidden="true" /></template>
      </BaseInput>
      <BaseInput
        v-else
        v-model="form.recovery_code"
        name="recovery_code"
        label="Recovery code"
        autocomplete="off"
        placeholder="xxxxx-xxxxx"
        required
        variant="glass"
        :error="form.errors.recovery_code"
      >
        <template #leading><KeyRound class="h-5 w-5" aria-hidden="true" /></template>
      </BaseInput>

      <BaseButton type="submit" size="lg" :loading="form.processing" class="mt-6 w-full hover:border-primary! hover:bg-primary/85!">Verify and sign in</BaseButton>
    </form>

    <div class="mt-6 flex flex-col items-center gap-3 text-small text-white/80">
      <button type="button" class="rounded-sm font-medium text-dark-primary underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-dark-primary" @click="toggle">
        {{ useRecovery ? 'Use the code from my app' : 'Lost your phone? Use a recovery code' }}
      </button>
      <Link href="/login" class="rounded-sm font-medium text-dark-primary underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-dark-primary">Back to sign in</Link>
    </div>
  </AuthShell>
</template>
