<script setup>
import IconButton from '../../components/IconButton.vue'
import { ArrowLeft } from '@lucide/vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseCard from '../../components/BaseCard.vue'

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
  <div class="mx-auto max-w-3xl space-y-6">
    <Head title="New academic year" />

    <header class="flex items-end justify-between gap-4">
      <div>
        <h1 class="text-h1 font-display font-semibold text-ink dark:text-dark-ink">New academic year</h1>
        <p class="mt-2 text-small text-muted dark:text-dark-muted">
          The calendar span that owns every semester, offering and grade of that year.
        </p>
      </div>
      <IconButton :icon="ArrowLeft" href="/academic-years" size="md" label="Back to academic years" />
    </header>

    <BaseCard padding="lg">
    <form class="space-y-5" @submit.prevent="submit">
      <div class="grid gap-5 sm:grid-cols-2">
        <BaseInput v-model="form.code" name="code" label="Code" placeholder="2026-2027" :error="form.errors.code" autofocus required />
        <BaseInput v-model="form.name" name="name" label="Name" placeholder="Academic Year 2026-2027" :error="form.errors.name" required />
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <BaseInput v-model="form.start_date" name="start_date" label="Start date" type="date" :error="form.errors.start_date" required />
        <BaseInput v-model="form.end_date" name="end_date" label="End date" type="date" :error="form.errors.end_date" required />
      </div>

      <BaseSelect v-model="form.status" label="Initial status" :options="STATUSES" :error="form.errors.status" />
      <p class="-mt-3 text-small text-muted dark:text-dark-muted">
          A year only moves forward: planned → active → completed.
      </p>

      <label class="flex min-h-11 items-start gap-3 text-small text-ink dark:text-dark-ink">
        <input
          v-model="form.is_current"
          type="checkbox"
          class="mt-1 h-4 w-4 rounded border-border-muted accent-primary focus-visible:outline-2 focus-visible:outline-primary"
        />
        <span>
          Make this the current academic year
          <span class="mt-1 block text-caption text-muted dark:text-dark-muted">
            Only an active year can be current, and only one year at a time.
          </span>
        </span>
      </label>

      <div class="flex items-center gap-3">
        <BaseButton type="submit" :loading="form.processing">Create academic year</BaseButton>
        <Link href="/academic-years"><BaseButton variant="ghost">Cancel</BaseButton></Link>
      </div>
    </form>
    </BaseCard>
  </div>
</template>
