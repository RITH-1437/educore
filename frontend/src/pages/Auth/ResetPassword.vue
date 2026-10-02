<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { CircleAlert, Lock, Mail } from '@lucide/vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'
import AuthShell from '../../components/auth/AuthShell.vue'

const props = defineProps({
  token: { type: String, required: true },
  email: { type: String, default: '' },
})

const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' })
const submit = () => form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<script>
import GuestLayout from '../../layouts/GuestLayout.vue'

export default { layout: GuestLayout }
</script>

<template>
  <Head title="Choose a new password" />
  <AuthShell title="Choose a new password" subtitle="At least 8 characters, with letters and numbers.">
    <div v-if="form.errors.email && !form.errors.password" class="mt-6 flex items-start gap-3 rounded-lg border border-red-300/40 bg-error/15 p-4" role="alert">
      <CircleAlert class="mt-0.5 h-5 w-5 shrink-0 text-red-200" aria-hidden="true" />
      <p class="text-small text-red-100">{{ form.errors.email }} <Link href="/forgot-password" class="font-medium underline">Request a new link</Link></p>
    </div>

    <form class="mt-8 space-y-4" @submit.prevent="submit">
      <BaseInput v-model="form.email" name="email" label="Email" type="email" autocomplete="email" required variant="glass">
        <template #leading><Mail class="h-5 w-5" aria-hidden="true" /></template>
      </BaseInput>
      <BaseInput v-model="form.password" name="password" label="New password" type="password" autocomplete="new-password" autofocus required variant="glass" :error="form.errors.password">
        <template #leading><Lock class="h-5 w-5" aria-hidden="true" /></template>
      </BaseInput>
      <BaseInput v-model="form.password_confirmation" name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required variant="glass">
        <template #leading><Lock class="h-5 w-5" aria-hidden="true" /></template>
      </BaseInput>
      <BaseButton type="submit" size="lg" :loading="form.processing" class="mt-2 w-full hover:border-primary! hover:bg-primary/85!">Save new password</BaseButton>
    </form>
  </AuthShell>
</template>
