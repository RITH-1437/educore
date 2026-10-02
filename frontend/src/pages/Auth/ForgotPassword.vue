<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { CircleCheck, Mail } from '@lucide/vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'
import AuthShell from '../../components/auth/AuthShell.vue'

defineProps({
  status: { type: String, default: null },
})

const form = useForm({ email: '' })
const submit = () => form.post('/forgot-password', { preserveScroll: true, onSuccess: () => form.reset() })
</script>

<script>
import GuestLayout from '../../layouts/GuestLayout.vue'

export default { layout: GuestLayout }
</script>

<template>
  <Head title="Forgot password" />
  <AuthShell title="Forgot your password?" subtitle="Enter your account email and we will send you a link to choose a new one.">
    <div v-if="status" class="mt-6 flex items-start gap-3 rounded-lg border border-green-300/40 bg-success/15 p-4" role="status">
      <CircleCheck class="mt-0.5 h-5 w-5 shrink-0 text-green-200" aria-hidden="true" />
      <p class="text-small text-green-100">{{ status }}</p>
    </div>

    <form class="mt-8" @submit.prevent="submit">
      <BaseInput v-model="form.email" name="email" label="Email" type="email" placeholder="you@university.edu" autocomplete="email" autofocus required variant="glass" :error="form.errors.email">
        <template #leading><Mail class="h-5 w-5" aria-hidden="true" /></template>
      </BaseInput>
      <BaseButton type="submit" size="lg" :loading="form.processing" class="mt-6 w-full hover:border-primary! hover:bg-primary/85!">Email me a reset link</BaseButton>
    </form>

    <p class="mt-6 text-center text-small text-white/80">
      <Link href="/login" class="rounded-sm font-medium text-dark-primary underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-dark-primary">Back to sign in</Link>
    </p>
  </AuthShell>
</template>
