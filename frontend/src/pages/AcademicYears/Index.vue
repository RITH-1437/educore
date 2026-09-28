<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import Pagination from '../../components/Pagination.vue'

const props = defineProps({
  academicYears: { type: Object, required: true },
  filters: { type: Object, default: () => ({ search: '', status: '' }) },
})

const page = usePage()
const flash = computed(() => page.props.flash)

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')

const STATUSES = [
  { value: '', label: 'All statuses' },
  { value: 'planned', label: 'Planned' },
  { value: 'active', label: 'Active' },
  { value: 'completed', label: 'Completed' },
]

const statusClasses = {
  planned: 'bg-slate-100 text-slate-700',
  active: 'bg-emerald-100 text-emerald-700',
  completed: 'bg-blue-100 text-blue-700',
}

const applyFilters = () =>
  router.get(
    '/academic-years',
    { search: search.value || undefined, filters: status.value ? { status: status.value } : undefined },
    { preserveState: true, replace: true },
  )

const changeStatus = (academicYear, nextStatus) => {
  router.post(`/academic-years/${academicYear.id}/status`, { status: nextStatus })
}

const makeCurrent = (academicYear) => {
  router.post(`/academic-years/${academicYear.id}/current`)
}

const deleteYear = (academicYear) => {
  if (window.confirm(`Delete academic year "${academicYear.code}"? This cannot be undone.`)) {
    router.delete(`/academic-years/${academicYear.id}`)
  }
}
</script>

<template>
  <div>
    <Head title="Academic years" />

    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-slate-900">Academic years</h2>
        <p class="mt-1 text-sm text-slate-600">The university calendar every course offering hangs from.</p>
      </div>
      <Link href="/academic-years/create">
        <BaseButton type="button">+ New academic year</BaseButton>
      </Link>
    </div>

    <div v-if="flash?.success" class="mt-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
      {{ flash.success }}
    </div>
    <div v-if="flash?.error" class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">
      {{ flash.error }}
    </div>

    <div class="mt-6 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
      <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 px-4 py-3">
        <form class="flex flex-1 items-center gap-3" @submit.prevent="applyFilters">
          <input
            v-model="search"
            type="search"
            placeholder="Search by code or name…"
            class="w-full max-w-xs rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600"
          />
          <select
            v-model="status"
            class="rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600"
            @change="applyFilters"
          >
            <option v-for="option in STATUSES" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
          <BaseButton type="submit" size="sm">Filter</BaseButton>
        </form>
      </div>

      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Code</th>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Span</th>
            <th class="px-4 py-3">Semesters</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Current</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <tr v-for="academicYear in academicYears.data" :key="academicYear.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-medium text-slate-900">{{ academicYear.code }}</td>
            <td class="px-4 py-3 text-slate-600">{{ academicYear.name }}</td>
            <td class="px-4 py-3 text-slate-500">
              {{ academicYear.start_date }} → {{ academicYear.end_date }}
            </td>
            <td class="px-4 py-3 text-slate-600">{{ academicYear.semesters_count ?? 0 }}</td>
            <td class="px-4 py-3">
              <span
                class="inline-flex rounded px-2 py-0.5 text-xs font-medium"
                :class="statusClasses[academicYear.status]"
              >
                {{ academicYear.status_label }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                v-if="academicYear.is_current"
                class="inline-flex rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700"
              >
                Current
              </span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-4 py-3 text-right">
              <Link
                :href="`/academic-years/${academicYear.id}/edit`"
                class="font-medium text-blue-600 hover:text-blue-500"
              >
                Edit
              </Link>
              <button
                v-if="academicYear.status === 'planned'"
                type="button"
                class="ml-4 font-medium text-blue-600 hover:text-blue-500"
                @click="changeStatus(academicYear, 'active')"
              >
                Activate
              </button>
              <button
                v-else-if="academicYear.status === 'active'"
                type="button"
                class="ml-4 font-medium text-blue-600 hover:text-blue-500"
                @click="changeStatus(academicYear, 'completed')"
              >
                Complete
              </button>
              <button
                v-if="academicYear.status === 'active' && !academicYear.is_current"
                type="button"
                class="ml-4 font-medium text-blue-600 hover:text-blue-500"
                @click="makeCurrent(academicYear)"
              >
                Make current
              </button>
              <button
                type="button"
                class="ml-4 font-medium text-red-600 hover:text-red-500"
                @click="deleteYear(academicYear)"
              >
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="academicYears.data.length === 0">
            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
              No academic years yet.
            </td>
          </tr>
        </tbody>
      </table>

      <Pagination :links="academicYears.links" />
    </div>
  </div>
</template>
