<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'

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
  <div class="max-w-2xl">
    <Head :title="`Edit · ${user.name}`" />

    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Edit user</h2>
        <p class="mt-1 text-sm text-gray-600">Update account details and access role.</p>
      </div>
      <Link href="/users" class="text-sm font-medium text-gray-500 hover:text-gray-700">← Back to users</Link>
    </div>

    <div v-if="flash?.success" class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">
      {{ flash.success }}
    </div>

    <form class="mt-6 space-y-5 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200" @submit.prevent="submit">
      <BaseInput v-model="form.name" label="Full name" :error="form.errors.name" autofocus />
      <BaseInput v-model="form.email" label="Email" type="email" :error="form.errors.email" />

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Role</label>
        <select
          v-model="form.role_id"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
        </select>
        <p v-if="form.errors.role_id" class="mt-1 text-sm text-red-600">{{ form.errors.role_id }}</p>
      </div>

      <BaseInput v-model="form.phone" label="Phone (optional)" :error="form.errors.phone" />

      <div class="grid gap-5 sm:grid-cols-2">
        <BaseInput v-model="form.password" label="New password" type="password" :error="form.errors.password" />
        <BaseInput v-model="form.password_confirmation" label="Confirm new password" type="password" />
      </div>
      <p class="text-xs text-gray-500">Leave the password fields empty to keep the current password.</p>

      <label class="flex items-center text-sm text-gray-600">
        <input
          v-model="form.is_active"
          type="checkbox"
          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        />
        <span class="ml-2">Active (can sign in)</span>
      </label>

      <div class="flex items-center gap-3">
        <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
        <Link href="/users" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</Link>
      </div>
    </form>
  </div>
</template>