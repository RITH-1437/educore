<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Plus, Search } from '@lucide/vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  rooms: { type: Object, required: true },
  types: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const { confirm } = useConfirm()
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))
const typeOptions = computed(() => props.types.map((value) => ({ value, label: value.charAt(0).toUpperCase() + value.slice(1) })))

const search = ref(props.filters.search ?? '')
const applySearch = () => router.get('/rooms', { search: search.value || undefined }, { preserveState: true, replace: true })

const editing = ref(null)
const show = ref(false)
const form = useForm({ code: '', name: '', building: '', floor: '', capacity: 40, room_type: 'lecture', is_active: true })

const openCreate = () => {
  editing.value = null
  form.reset()
  form.clearErrors()
  show.value = true
}
const openEdit = (room) => {
  editing.value = room
  form.clearErrors()
  Object.assign(form, { code: room.code, name: room.name, building: room.building ?? '', floor: room.floor ?? '', capacity: room.capacity, room_type: room.room_type, is_active: room.is_active })
  show.value = true
}
const submit = () => {
  const options = { preserveScroll: true, onSuccess: () => { show.value = false } }
  editing.value ? form.put(`/rooms/${editing.value.id}`, options) : form.post('/rooms', options)
}
const destroy = async (room) => {
  if (await confirm({ title: `Delete room ${room.code}?`, message: 'Refused while classes are scheduled in it — deactivate it instead.', confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/rooms/${room.id}`, { preserveScroll: true })
  }
}

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'name', label: 'Room' },
  { key: 'room_type', label: 'Type' },
  { key: 'capacity', label: 'Seats', align: 'center' },
  { key: 'schedule_entries_count', label: 'Weekly classes', align: 'center' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]
</script>

<template>
  <Head title="Rooms - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Timetable" title="Rooms" description="Teaching spaces used by section schedules. A room cannot host two classes at once in a semester, and must seat the students already enrolled.">
      <template v-if="canManage" #actions>
        <BaseButton @click="openCreate"><Plus class="h-4 w-4" aria-hidden="true" /> New room</BaseButton>
      </template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="applySearch">
        <BaseInput v-model="search" label="Search" placeholder="Code, name or building" class="w-full sm:max-w-sm" />
        <BaseButton type="submit" variant="secondary"><Search class="h-4 w-4" aria-hidden="true" /> Search</BaseButton>
      </form>
    </BaseCard>

    <BaseTable :columns="columns" :rows="rooms.data" caption="Rooms" empty-title="No rooms yet" empty-description="Add the rooms where classes take place.">
      <template #cell-code="{ row }"><span class="font-mono font-semibold">{{ row.code }}</span></template>
      <template #cell-name="{ row }">
        <p class="font-medium">{{ row.name }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ [row.building, row.floor && `floor ${row.floor}`].filter(Boolean).join(' · ') || '—' }}</p>
      </template>
      <template #cell-room_type="{ row }"><span class="capitalize">{{ row.room_type }}</span></template>
      <template #cell-is_active="{ row }"><StatusBadge :status="row.is_active ? 'active' : 'inactive'" /></template>
      <template #cell-actions="{ row }">
        <div v-if="canManage" class="flex justify-end gap-4">
          <button type="button" class="text-small font-semibold text-primary hover:underline dark:text-dark-primary" @click="openEdit(row)">Edit</button>
          <button type="button" class="text-small font-semibold text-error hover:underline dark:text-red-300" @click="destroy(row)">Delete</button>
        </div>
      </template>
    </BaseTable>

    <Pagination :links="rooms.links" />

    <BaseModal v-model="show" :title="editing ? `Edit ${editing.code}` : 'New room'" size="md">
      <form class="space-y-5" @submit.prevent="submit">
        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="form.code" name="code" label="Code" placeholder="B-201" :error="form.errors.code" required />
          <BaseSelect v-model="form.room_type" label="Type" :options="typeOptions" :error="form.errors.room_type" />
        </div>
        <BaseInput v-model="form.name" name="name" label="Name" :error="form.errors.name" required />
        <div class="grid gap-5 sm:grid-cols-3">
          <BaseInput v-model="form.building" name="building" label="Building" :error="form.errors.building" />
          <BaseInput v-model="form.floor" name="floor" label="Floor" :error="form.errors.floor" />
          <BaseInput v-model="form.capacity" name="capacity" label="Seats" type="number" min="1" :error="form.errors.capacity" required />
        </div>
        <label v-if="editing" class="flex min-h-11 items-center gap-2 text-small text-ink dark:text-dark-ink">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 accent-primary" /> Active (inactive rooms cannot receive new classes)
        </label>
        <ErrorAlert v-if="Object.keys(form.errors).length" title="Check the form" message="Correct the highlighted fields and try again." />
        <div class="flex gap-3">
          <BaseButton type="submit" :loading="form.processing">{{ editing ? 'Save' : 'Create room' }}</BaseButton>
          <BaseButton variant="ghost" @click="show = false">Cancel</BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
