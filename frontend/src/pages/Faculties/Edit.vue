<script setup>
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
  faculty: { type: Object, required: true },
  universities: { type: Object, required: true },
})

const form = useForm({
  university_id: props.faculty.university_id,
  code: props.faculty.code,
  name: props.faculty.name,
  dean_name: props.faculty.dean_name ?? '',
  description: props.faculty.description ?? '',
})

const universityOptions = computed(() =>
  (props.universities?.data ?? []).map((university) => ({
    value: university.id,
    label: `${university.code} — ${university.name}`,
  })),
)

const submit = () => form.put(`/faculties/${props.faculty.id}`, { preserveScroll: true })
</script>

<template>
  <Head :title="`Edit ${faculty.code} - EduCore`" />
  <div class="mx-auto max-w-3xl space-y-6">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-caption font-semibold uppercase tracking-widest text-primary">University structure</p>
        <h1 class="mt-2 text-h1 font-display font-semibold text-ink dark:text-dark-ink">
          {{ faculty.name }}
        </h1>
        <p class="mt-2 text-small text-muted dark:text-dark-muted">
          Renaming keeps every department, program, course and lecturer pointing at the same faculty id.
        </p>
      </div>
      <Link href="/faculties" class="text-small font-semibold text-primary hover:underline dark:text-dark-primary">
        Back to faculties
      </Link>
    </header>

    <div class="flex items-center gap-3">
      <StatusBadge :status="faculty.is_active ? 'active' : 'archived'" />
      <span class="text-small text-muted dark:text-dark-muted">
        {{ faculty.departments_count ?? 0 }} department(s)
      </span>
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
        <BaseSelect v-model="form.university_id" label="University" :options="universityOptions" :error="form.errors.university_id" required />

        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="form.code" label="Code" placeholder="ENG" :error="form.errors.code" required />
          <BaseInput v-model="form.name" label="Name" placeholder="Faculty of Engineering" :error="form.errors.name" required />
        </div>

        <BaseInput v-model="form.dean_name" label="Dean" placeholder="Dr. Sokha Chan" :error="form.errors.dean_name" />
        <BaseTextarea v-model="form.description" label="Description" rows="4" :error="form.errors.description" />

        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
          <Link href="/faculties"><BaseButton variant="ghost">Cancel</BaseButton></Link>
        </div>
      </form>
    </BaseCard>
  </div>
</template>