<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'

const STATUSES = [
  { value: 'planned', label: 'Planned' },
  { value: 'active', label: 'Active' },
]

const form = useForm({
  code: '',
  name: '',
  start_date: '',
  end_date: '',
  status: 'planned',
  is_current: false,
})

const submit = () => form.post('/academic-years', { preserveScroll: true })
</script>

<template>
  <div class="max-w-2xl">
    <Head title="New academic year" />

    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-slate-900">New academic year</h2>
        <p class="mt-1 text-sm text-slate-600">
          The calendar span that owns every semester, offering and grade of that year.
        </p>
      </div>
      <Link href="/academic-years" class="text-sm font-medium text-slate-500 hover:text-slate-700">
        ← Back to academic years
      </Link>
    </div>

    <form
      class="mt-6 space-y-5 rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200"
      @submit.prevent="submit"
    >
      <div class="grid gap-5 sm:grid-cols-2">
        <BaseInput v-model="form.code" label="Code" placeholder="2026-2027" :error="form.errors.code" autofocus />
        <BaseInput v-model="form.name" label="Name" placeholder="Academic Year 2026-2027" :error="form.errors.name" />
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <BaseInput v-model="form.start_date" label="Start date" type="date" :error="form.errors.start_date" />
        <BaseInput v-model="form.end_date" label="End date" type="date" :error="form.errors.end_date" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
        <select
          v-model="form.status"
          class="block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 sm:text-sm"
        >
          <option v-for="option in STATUSES" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <p class="mt-1 text-sm text-slate-500">
          A year only moves forward: planned → active → completed.
        </p>
        <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
      </div>

      <label class="flex items-start text-sm text-slate-600">
        <input
          v-model="form.is_current"
          type="checkbox"
          class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-600"
        />
        <span class="ml-2">
          Make this the current academic year
          <span class="block text-xs text-slate-500">
            Only an active year can be current, and only one year at a time.
          </span>
        </span>
      </label>

      <div class="flex items-center gap-3">
        <BaseButton type="submit" :loading="form.processing">Create academic year</BaseButton>
        <Link href="/academic-years" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</Link>
      </div>
    </form>
  </div>
</template>
