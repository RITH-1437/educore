<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Plus, Search } from '@lucide/vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import PageHeader from '../../components/PageHeader.vue'
import { useConfirm } from '../../composables/useConfirm'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseTable from '../../components/BaseTable.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import Pagination from '../../components/Pagination.vue'

const props = defineProps({
  universities: { type: Object, required: true },
  filters: { type: Object, default: () => ({ search: '' }) },
})

const page = usePage()

// Faculty Admin may read the structure but not change it, so every write
// control is hidden rather than left to fail with a 403
// (`skills/faculty-department/SKILL.md` §8).
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))

const search = ref(props.filters.search ?? '')
const showCreate = ref(false)

const createForm = useForm({
  code: '',
  name: '',
  short_name: '',
  address: '',
  phone: '',
  email: '',
  website: '',
})

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'name', label: 'University' },
  { key: 'short_name', label: 'Short name' },
  { key: 'faculties_count', label: 'Faculties', align: 'center' },
  { key: 'is_current', label: 'State' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const isEmpty = computed(() => (props.universities?.data ?? []).length === 0)

const applyFilters = () =>
  router.get('/universities', { search: search.value || undefined }, { preserveState: true, replace: true })

const openCreate = () => {
  createForm.reset()
  showCreate.value = true
}

const submitCreate = () =>
  createForm.post('/universities', {
    preserveScroll: true,
    onSuccess: () => {
      showCreate.value = false
      createForm.reset()
    },
  })

const makeCurrent = (university) => router.post(`/universities/${university.id}/current`)

const { confirm } = useConfirm()

const destroy = async (university) => {
  if (await confirm({ title: 'Delete university?', message: `Delete "${university.name}"? This is refused while it still has faculties.`, confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/universities/${university.id}`)
  }
}
</script>

<template>
  <Head title="University - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="University structure" title="University" description="The single institution record that owns every faculty, program and course.">
      <template #actions>
        <BaseButton href="/faculties" variant="secondary">Manage faculties</BaseButton>
        <BaseButton v-if="canManage" @click="openCreate"><Plus class="h-4 w-4" aria-hidden="true" /> New university</BaseButton>
      </template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="applyFilters">
        <BaseInput v-model="search" label="Search" placeholder="Code or name" class="w-full sm:max-w-xs" />
        <BaseButton type="submit" variant="secondary"><Search class="h-4 w-4" aria-hidden="true" /> Search</BaseButton>
      </form>
    </BaseCard>

    <BaseTable
      :columns="columns"
      :rows="universities.data"
      empty-title="No universities found"
      empty-description="Create the institution record to start building the academic structure."
    >
      <template #empty-action>
        <BaseButton v-if="isEmpty && !search && canManage" @click="openCreate">+ New university</BaseButton>
      </template>
      <template #cell-code="{ row }">
        <span class="font-semibold text-ink dark:text-dark-ink">{{ row.code }}</span>
      </template>
      <template #cell-name="{ row }">
        <div>
          <p class="font-medium text-ink dark:text-dark-ink">{{ row.name }}</p>
          <p v-if="row.email" class="text-caption text-muted dark:text-dark-muted">{{ row.email }}</p>
        </div>
      </template>
      <template #cell-short_name="{ row }">
        <span class="text-muted dark:text-dark-muted">{{ row.short_name ?? '—' }}</span>
      </template>
      <template #cell-faculties_count="{ row }">
        <span class="font-semibold text-ink dark:text-dark-ink">{{ row.faculties_count ?? 0 }}</span>
      </template>
      <template #cell-is_current="{ row }">
        <BaseBadge :variant="row.is_current ? 'success' : 'muted'" :dot="row.is_current">
          {{ row.is_current ? 'Current' : 'Other' }}
        </BaseBadge>
      </template>
      <template #cell-actions="{ row }">
        <div v-if="canManage" class="flex justify-end gap-3">
          <Link :href="`/universities/${row.id}/edit`" class="text-small font-semibold text-primary hover:underline dark:text-dark-primary">Edit</Link>
          <button
            v-if="!row.is_current"
            type="button"
            class="text-small font-semibold text-success hover:underline"
            @click="makeCurrent(row)"
          >
            Make current
          </button>
          <button type="button" class="text-small font-semibold text-error hover:underline" @click="destroy(row)">Delete</button>
        </div>
        <span v-else class="block text-right text-caption text-muted dark:text-dark-muted">Read only</span>
      </template>
    </BaseTable>

    <Pagination :links="universities.links" />

    <BaseModal v-model="showCreate" title="New university" size="md">
      <form class="space-y-5" @submit.prevent="submitCreate">
        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="createForm.code" label="Code" placeholder="ITC" :error="createForm.errors.code" required />
          <BaseInput v-model="createForm.short_name" label="Short name" placeholder="ITC" :error="createForm.errors.short_name" />
        </div>
        <BaseInput v-model="createForm.name" label="Name" placeholder="Institute of Technology Cambodia" :error="createForm.errors.name" required />
        <BaseTextarea v-model="createForm.address" label="Address" rows="3" :error="createForm.errors.address" />
        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="createForm.phone" label="Phone" placeholder="+855 23 883 222" :error="createForm.errors.phone" />
          <BaseInput v-model="createForm.email" label="Email" type="email" placeholder="info@educore.kh" :error="createForm.errors.email" />
        </div>
        <BaseInput v-model="createForm.website" label="Website" type="url" placeholder="https://www.educore.kh" :error="createForm.errors.website" />

        <ErrorAlert
          v-if="Object.keys(createForm.errors).length"
          title="Check the form"
          message="Correct the highlighted fields and try again."
        />

        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="createForm.processing">Create university</BaseButton>
          <BaseButton variant="ghost" @click="showCreate = false">Cancel</BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>