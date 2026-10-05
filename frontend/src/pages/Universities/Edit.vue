<script setup>
import IconButton from '../../components/IconButton.vue'
import { ArrowLeft, Star, Trash2 } from '@lucide/vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  university: { type: Object, required: true },
})

const form = useForm({
  code: props.university.code,
  name: props.university.name,
  short_name: props.university.short_name ?? '',
  address: props.university.address ?? '',
  phone: props.university.phone ?? '',
  email: props.university.email ?? '',
  website: props.university.website ?? '',
  is_current: props.university.is_current,
})

const submit = () => form.put(`/universities/${props.university.id}`, { preserveScroll: true })

const makeCurrent = () => router.post(`/universities/${props.university.id}/current`)

const { confirm } = useConfirm()

const destroy = async () => {
  if (await confirm({ title: 'Delete university?', message: `Delete "${props.university.name}"? This is refused while it still has departments.`, confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/universities/${props.university.id}`)
  }
}
</script>

<template>
  <Head :title="`Edit ${university.code} - EduCore`" />
  <div class="mx-auto max-w-3xl space-y-6">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <p class="text-caption font-semibold uppercase tracking-widest text-primary">University structure</p>
        <h1 class="mt-2 text-h1 font-display font-semibold text-ink dark:text-dark-ink">{{ university.name }}</h1>
        <p class="mt-2 text-small text-muted dark:text-dark-muted">
          Platform-wide record. Exactly one university may be current at a time.
        </p>
      </div>
      <div class="flex flex-wrap gap-3">
        <IconButton :icon="ArrowLeft" href="/universities" size="md" label="Back to universities" />
        <IconButton :icon="Star" size="md" :label="university.is_current ? 'Already the current university' : 'Make current'" :disabled="university.is_current" @click="makeCurrent" />
      </div>
    </header>

    <BaseCard padding="md">
      <dl class="grid gap-4 sm:grid-cols-3">
        <div>
          <dt class="text-caption uppercase tracking-wide text-muted dark:text-dark-muted">Departments</dt>
          <dd class="mt-1 text-h4 font-semibold text-ink dark:text-dark-ink">{{ university.departments_count ?? 0 }}</dd>
        </div>
        <div>
          <dt class="text-caption uppercase tracking-wide text-muted dark:text-dark-muted">Current</dt>
          <dd class="mt-1">
            <span v-if="university.is_current" class="text-h4 font-semibold text-success">Yes</span>
            <span v-else class="text-h4 font-semibold text-muted dark:text-dark-muted">No</span>
          </dd>
        </div>
        <div>
          <dt class="text-caption uppercase tracking-wide text-muted dark:text-dark-muted">Website</dt>
          <dd class="mt-1 text-small">
            <a v-if="university.website" :href="university.website" class="text-primary hover:underline dark:text-dark-primary">{{ university.website }}</a>
            <span v-else class="text-muted dark:text-dark-muted">—</span>
          </dd>
        </div>
      </dl>
    </BaseCard>

    <ErrorAlert v-if="Object.keys(form.errors).length" title="Check the form" message="Correct the highlighted fields and try again.">
      <ul class="list-disc space-y-1 pl-4">
        <li v-for="(messages, field) in form.errors" :key="field">
          <span class="font-semibold">{{ field }}</span>: {{ Array.isArray(messages) ? messages[0] : messages }}
        </li>
      </ul>
    </ErrorAlert>

    <BaseCard padding="lg">
      <form class="space-y-5" @submit.prevent="submit">
        <div class="grid gap-5 sm:grid-cols-3">
          <BaseInput v-model="form.code" label="Code" placeholder="ITC" :error="form.errors.code" required />
          <BaseInput v-model="form.short_name" label="Short name" placeholder="ITC" :error="form.errors.short_name" />
          <BaseInput v-model="form.phone" label="Phone" placeholder="+855 23 883 222" :error="form.errors.phone" />
        </div>

        <BaseInput v-model="form.name" label="Name" placeholder="Institute of Technology Cambodia" :error="form.errors.name" required />
        <BaseTextarea v-model="form.address" label="Address" rows="3" :error="form.errors.address" />

        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="form.email" label="Email" type="email" placeholder="info@educore.kh" :error="form.errors.email" />
          <BaseInput v-model="form.website" label="Website" type="url" placeholder="https://www.educore.kh" :error="form.errors.website" />
        </div>

        <label class="flex min-h-11 items-start gap-3 text-small text-ink dark:text-dark-ink">
          <input
            v-model="form.is_current"
            type="checkbox"
            class="mt-1 h-4 w-4 rounded border-border-muted accent-primary focus-visible:outline-2 focus-visible:outline-primary"
          />
          <span>
            Make this the current university
            <span class="mt-1 block text-caption text-muted dark:text-dark-muted">
              Promoting a university clears the flag on the previous one.
            </span>
          </span>
        </label>

        <div class="flex flex-wrap items-center gap-3">
          <BaseButton type="submit" :loading="form.processing">Save changes</BaseButton>
          <IconButton :icon="Trash2" size="md" variant="danger" label="Delete university" @click="destroy" />
        </div>
      </form>
    </BaseCard>
  </div>
</template>