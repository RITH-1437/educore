<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import PageHeader from '../../components/PageHeader.vue'

const form = useForm({ current_password: '', password: '', password_confirmation: '' })
const save = () => form.put('/account/password', { preserveScroll: true, onFinish: () => form.reset() })
</script>

<template>
  <Head title="Change password - EduCore" />
  <div class="mx-auto max-w-xl space-y-6">
    <PageHeader eyebrow="Account" title="Change password" description="Other devices and browsers signed in to your account will be signed out. You will receive an email confirming the change." />

    <BaseCard padding="lg">
      <form class="space-y-4" @submit.prevent="save">
        <BaseInput v-model="form.current_password" name="current_password" label="Current password" type="password" autocomplete="current-password" required :error="form.errors.current_password" />
        <BaseInput v-model="form.password" name="password" label="New password" type="password" autocomplete="new-password" required hint="At least 8 characters, with letters and numbers." :error="form.errors.password" />
        <BaseInput v-model="form.password_confirmation" name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
        <div class="flex justify-end"><BaseButton type="submit" :loading="form.processing">Change password</BaseButton></div>
      </form>
    </BaseCard>
  </div>
</template>
