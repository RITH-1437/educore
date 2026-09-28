<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'

const props = defineProps({
  academicYear: { type: Object, required: true },
  semesters: { type: Array, required: true },
})

const page = usePage()
const flash = computed(() => page.props.flash)

const form = useForm({
  code: props.academicYear.code,
  name: props.academicYear.name,
  start_date: props.academicYear.start_date ?? '',
  end_date: props.academicYear.end_date ?? '',
})

const semesterForm = useForm({
  name: '',
  code: '',
  sequence: '',
  start_date: '',
  end_date: '',
})

const statusClasses = {
  planned: 'bg-slate-100 text-slate-700',
  open: 'bg-emerald-100 text-emerald-700',
  closed: 'bg-amber-100 text-amber-700',
  completed: 'bg-blue-100 text-blue-700',
}

const yearStatusClasses = {
  planned: 'bg-slate-100 text-slate-700',
  active: 'bg-emerald-100 text-emerald-700',
  completed: 'bg-blue-100 text-blue-700',
}

const submit = () => form.put(`/academic-years/${props.academicYear.id}`, { preserveScroll: true })

const addSemester = () =>
  semesterForm.post(`/academic-years/${props.academicYear.id}/semesters`, {
    preserveScroll: true,
    onSuccess: () => semesterForm.reset(),
  })

const changeSemesterStatus = (semester, status) =>
  router.post(`/academic-years/${props.academicYear.id}/semesters/${semester.id}/status`, { status })

const deleteSemester = (semester) => {
  if (window.confirm(`Delete "${semester.name}"? This cannot be undone.`)) {
    router.delete(`/academic-years/${props.academicYear.id}/semesters/${semester.id}`, {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <div>
    <Head :title="`Edit ${academicYear.code}`" />

    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-slate-900">{{ academicYear.name }}</h2>
        <p class="mt-1 text-sm text-slate-600">
          Code <span class="font-medium text-slate-900">{{ academicYear.code }}</span>
          <span
            class="ml-2 inline-flex rounded px-2 py-0.5 text-xs font-medium"
            :class="yearStatusClasses[academicYear.status]"
          >
            {{ academicYear.status_label }}
          </span>
          <span
            v-if="academicYear.is_current"
            class="ml-2 inline-flex rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700"
          >
            Current
          </span>
        </p>
      </div>
      <Link href="/academic-years" class="text-sm font-medium text-slate-500 hover:text-slate-700">
        ← Back to academic years
      </Link>
    </div>

    <div v-if="flash?.success" class="mt-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
      {{ flash.success }}
    </div>
    <div v-if="flash?.error" class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">
      {{ flash.error }}
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
      <form
        class="space-y-5 rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200"
        @submit.prevent="submit"
      >
        <h3 class="text-lg font-semibold text-slate-900">Details</h3>

        <BaseInput v-model="form.code" label="Code" :error="form.errors.code" />
        <BaseInput v-model="form.name" label="Name" :error="form.errors.name" />

        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="form.start_date" label="Start date" type="date" :error="form.errors.start_date" />
          <BaseInput v-model="form.end_date" label="End date" type="date" :error="form.errors.end_date" />
        </div>

        <p class="text-xs text-slate-500">
          Status changes are made from the list (planned → active → completed) so the transition rules apply.
        </p>

        <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
      </form>

      <div class="space-y-6">
        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
          <h3 class="text-lg font-semibold text-slate-900">Semesters</h3>
          <p class="mt-1 text-sm text-slate-600">
            Ordered by sequence; every course offering hangs from a semester.
          </p>

          <table class="mt-4 min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-3 py-2">#</th>
                <th class="px-3 py-2">Name</th>
                <th class="px-3 py-2">Span</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr v-for="semester in semesters" :key="semester.id">
                <td class="px-3 py-2 text-slate-500">{{ semester.sequence }}</td>
                <td class="px-3 py-2 font-medium text-slate-900">{{ semester.name }}</td>
                <td class="px-3 py-2 text-slate-500">
                  {{ semester.start_date ?? '—' }} → {{ semester.end_date ?? '—' }}
                </td>
                <td class="px-3 py-2">
                  <span
                    class="inline-flex rounded px-2 py-0.5 text-xs font-medium"
                    :class="statusClasses[semester.status]"
                  >
                    {{ semester.status_label }}
                  </span>
                </td>
                <td class="px-3 py-2 text-right">
                  <button
                    v-if="semester.status === 'planned'"
                    type="button"
                    class="font-medium text-blue-600 hover:text-blue-500"
                    @click="changeSemesterStatus(semester, 'open')"
                  >
                    Open
                  </button>
                  <button
                    v-else-if="semester.status === 'open'"
                    type="button"
                    class="font-medium text-amber-600 hover:text-amber-500"
                    @click="changeSemesterStatus(semester, 'closed')"
                  >
                    Close
                  </button>
                  <button
                    type="button"
                    class="ml-3 font-medium text-red-600 hover:text-red-500"
                    @click="deleteSemester(semester)"
                  >
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="semesters.length === 0">
                <td colspan="5" class="px-3 py-6 text-center text-slate-500">No semesters yet.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <form
          class="space-y-4 rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200"
          @submit.prevent="addSemester"
        >
          <h3 class="text-lg font-semibold text-slate-900">Add a semester</h3>

          <div class="grid gap-4 sm:grid-cols-3">
            <BaseInput v-model="semesterForm.name" label="Name" placeholder="Semester 3" :error="semesterForm.errors.name" />
            <BaseInput v-model="semesterForm.code" label="Code" placeholder="S3" :error="semesterForm.errors.code" />
            <BaseInput v-model="semesterForm.sequence" label="Sequence" type="number" :error="semesterForm.errors.sequence" />
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <BaseInput v-model="semesterForm.start_date" label="Start date" type="date" :error="semesterForm.errors.start_date" />
            <BaseInput v-model="semesterForm.end_date" label="End date" type="date" :error="semesterForm.errors.end_date" />
          </div>

          <p class="text-xs text-slate-500">
            Dates must fall inside {{ academicYear.start_date }} → {{ academicYear.end_date }}.
          </p>

          <BaseButton type="submit" :loading="semesterForm.processing">Add semester</BaseButton>
        </form>
      </div>
    </div>
  </div>
</template>
