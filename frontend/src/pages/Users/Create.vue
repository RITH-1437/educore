<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseCard from '../../components/BaseCard.vue'

const props = defineProps({
  roles: { type: Array, required: true },
})

const form = useForm({
  name: '',
  email: '',
  phone: '',
  role_id: props.roles[0]?.id ?? null,
  is_active: true,
  password: '',
  password_confirmation: '',
})

const submit = () => form.post('/users', { preserveScroll: true })
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <Head title="New user" />

    <header class="flex items-end justify-between gap-4">
      <div>
        <h2 class="text-h1 font-display font-semibold text-ink dark:text-dark-ink">New user</h2>
        <p class="mt-2 text-small text-muted dark:text-dark-muted">Create a portal account and assign its access role.</p>
      </div>
      <Link href="/users" class="text-small font-semibold text-primary hover:underline dark:text-dark-primary">Back to users</Link>
    </header>

    <BaseCard padding="lg">
      <form class="space-y-5" @submit.prevent="submit">
      <BaseInput v-model="form.name" name="name" label="Full name" :error="form.errors.name" autofocus required />
      <BaseInput v-model="form.email" name="email" label="Email" type="email" :error="form.errors.email" required />

      <BaseSelect v-model="form.role_id" label="Role" :options="roles.map((role) => ({ value: role.id, label: role.name }))" placeholder="Select a role" :error="form.errors.role_id" required />

      <BaseInput v-model="form.phone" name="phone" label="Phone (optional)" :error="form.errors.phone" />

      <div class="grid gap-5 sm:grid-cols-2">
        <BaseInput v-model="form.password" name="password" label="Password" type="password" :error="form.errors.password" required />
        <BaseInput v-model="form.password_confirmation" name="password_confirmation" label="Confirm password" type="password" required />
      </div>

      <label class="flex min-h-11 items-center gap-3 text-small text-ink dark:text-dark-ink">
        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-border-muted accent-primary focus-visible:outline-2 focus-visible:outline-primary" />
        <span>Active (can sign in)</span>
      </label>

      <div class="flex items-center gap-3">
        <BaseButton type="submit" :loading="form.processing">Create user</BaseButton>
        <Link href="/users"><BaseButton variant="ghost">Cancel</BaseButton></Link>
      </div>
      </form>
    </BaseCard>
  </div>
</template>
