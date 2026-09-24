<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import Pagination from '../../components/Pagination.vue'

const props = defineProps({
  users: { type: Object, required: true },
  filters: { type: Object, default: () => ({ search: '' }) },
})

const page = usePage()
const flash = computed(() => page.props.flash)

const search = ref(props.filters.search ?? '')

const doSearch = () => {
  router.get('/users', { search: search.value || undefined }, { preserveState: true, replace: true })
}

function deleteUser(user) {
  if (window.confirm(`Delete user "${user.name}"? This cannot be undone.`)) {
    router.delete(`/users/${user.id}`)
  }
}
</script>

<template>
  <div>
    <Head title="Users" />

    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Users</h2>
        <p class="mt-1 text-sm text-gray-600">Manage portal accounts and access rights.</p>
      </div>
      <Link href="/users/create">
        <BaseButton type="button">+ New user</BaseButton>
      </Link>
    </div>

    <div v-if="flash?.success" class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">
      {{ flash.success }}
    </div>

    <div class="mt-6 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
      <div class="border-b border-gray-200 px-4 py-3">
        <form @submit.prevent="doSearch">
          <input
            v-model="search"
            type="search"
            placeholder="Search by name or email…"
            class="w-full max-w-sm rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
        </form>
      </div>

      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
          <tr>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">Role</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Created</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ user.name }}</td>
            <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                {{ user.role?.name ?? '—' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex rounded px-2 py-0.5 text-xs font-medium"
                :class="user.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
              >
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-500">{{ new Date(user.created_at).toLocaleDateString() }}</td>
            <td class="px-4 py-3 text-right">
              <Link :href="`/users/${user.id}/edit`" class="font-medium text-indigo-600 hover:text-indigo-500">Edit</Link>
              <button
                type="button"
                class="ml-4 font-medium text-red-600 hover:text-red-500"
                @click="deleteUser(user)"
              >
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="users.data.length === 0">
            <td colspan="6" class="px-4 py-10 text-center text-gray-500">No users found.</td>
          </tr>
        </tbody>
      </table>

      <Pagination :links="users.links" />
    </div>
  </div>
</template>