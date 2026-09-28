<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseCard from '../../components/BaseCard.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  user: { type: Object, required: true },
  roles: { type: Array, required: true },
})

const page = usePage()
const flash = computed(() => page.props.flash)

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone ?? '',
  role_id: props.user.role?.id ?? props.roles[0]?.id,
  is_active: props.user.is_active,
  password: '',
  password_confirmation: '',
})

const submit = () => form.put(`/users/${props.user.id}`, { preserveScroll: true })
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <Head :title="`Edit · ${user.name}`" />

    <header class="flex items-end justify-between gap-4">
      <div>
        <h2 class="text-h1 font-display font-semibold text-ink dark:text-dark-ink">Edit user</h2>
        <p class="mt-2 text-small text-muted dark:text-dark-muted">Update account details and access role.</p>
      </div>
      <Link href="/users" class="text-small font-semibold text-primary hover:underline dark:text-dark-primary">Back to users</Link>
    </header>

    <div v-if="flash?.success" class="rounded-lg border border-success/20 bg-success/5 px-4 py-3 text-small text-success" role="status">
      {{ flash.success }}
    </div>

    <BaseCard padding="lg">
      <form class="space-y-5" @submit.prevent="submit">
      <BaseInput v-model="form.name" name="name" label="Full name" :error="form.errors.name" autofocus required />
      <BaseInput v-model="form.email" name="email" label="Email" type="email" :error="form.errors.email" required />

      <BaseSelect v-model="form.role_id" label="Role" :options="roles.map((role) => ({ value: role.id, label: role.name }))" placeholder="Select a role" :error="form.errors.role_id" required />

      <BaseInput v-model="form.phone" name="phone" label="Phone (optional)" :error="form.errors.phone" />

      <div class="grid gap-5 sm:grid-cols-2">
        <BaseInput v-model="form.password" name="password" label="New password" type="password" :error="form.errors.password" />
        <BaseInput v-model="form.password_confirmation" name="password_confirmation" label="Confirm new password" type="password" />
      </div>
      <p class="text-small text-muted dark:text-dark-muted">Leave the password fields empty to keep the current password.</p>

      <label class="flex min-h-11 items-center gap-3 text-small text-ink dark:text-dark-ink">
        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-border-muted accent-primary focus-visible:outline-2 focus-visible:outline-primary" />
        <span>Active (can sign in)</span>
        <StatusBadge :status="form.is_active ? 'active' : 'inactive'" />
      </label>

      <div class="flex items-center gap-3">
        <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
        <Link href="/users"><BaseButton variant="ghost">Cancel</BaseButton></Link>
      </div>
      </form>
    </BaseCard>
  </div>
</template>
