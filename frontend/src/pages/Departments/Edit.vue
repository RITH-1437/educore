<script setup>
import IconButton from '../../components/IconButton.vue'
import { ArrowLeft } from '@lucide/vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  department: { type: Object, required: true },
  universities: { type: Array, required: true },
})

const form = useForm({
  university_id: props.department.university_id,
  code: props.department.code,
  name: props.department.name,
  head_name: props.department.head_name ?? '',
  description: props.department.description ?? '',
})

const universityOptions = computed(() =>
  props.universities.map((university) => ({ value: university.id, label: `${university.code} — ${university.name}` })),
)

const submit = () => form.put(`/departments/${props.department.id}`, { preserveScroll: true })
</script>

<template>
  <Head :title="`Edit ${department.code} - EduCore`" />
  <div class="mx-auto max-w-3xl space-y-6">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-caption font-semibold uppercase tracking-widest text-primary">University structure</p>
        <h1 class="mt-2 text-h1 font-display font-semibold text-ink dark:text-dark-ink">
          {{ department.name }}
        </h1>
        <p class="mt-2 text-small text-muted dark:text-dark-muted">
          Renaming keeps every program, course and lecturer pointing at the same department.
        </p>
      </div>
      <IconButton :icon="ArrowLeft" href="/departments" size="md" label="Back to departments" />
    </header>

    <div class="flex items-center gap-3">
      <StatusBadge :status="department.is_active ? 'active' : 'archived'" />
      <span class="text-small text-muted dark:text-dark-muted">{{ department.programs_count ?? 0 }} program(s)</span>
    </div>

    <ErrorAlert v-if="Object.keys(form.errors).length" title="Check the form" message="Correct the highlighted fields and try again.">
      <ul class="list-disc space-y-1 pl-4">
        <li v-for="(messages, field) in form.errors" :key="field">
          <span class="font-semibold">{{ field }}</span>: {{ Array.isArray(messages) ? messages[0] : messages }}
        </li>
      </ul>
    </ErrorAlert>

    <BaseCard padding="lg">
      <form class="space-y-5" @submit.prevent="submit">
        <BaseSelect v-if="universities.length > 1" v-model="form.university_id" label="University" :options="universityOptions" :error="form.errors.university_id" required />

        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="form.code" label="Code" placeholder="CSE" :error="form.errors.code" required />
          <BaseInput v-model="form.name" label="Name" placeholder="Department of Computer Science" :error="form.errors.name" required />
        </div>

        <BaseInput v-model="form.head_name" label="Head" placeholder="Dr. Dara Lim" :error="form.errors.head_name" />
        <BaseTextarea v-model="form.description" label="Description" rows="4" :error="form.errors.description" />

        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
          <Link href="/departments"><BaseButton variant="ghost">Cancel</BaseButton></Link>
        </div>
      </form>
    </BaseCard>
  </div>
</template>
