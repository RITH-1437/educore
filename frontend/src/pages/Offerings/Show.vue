<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import SectionCard from '../../components/offerings/SectionCard.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  offering: { type: Object, required: true },
  lecturers: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  sectionStatuses: { type: Array, default: () => [] },
  lecturerRoles: { type: Array, default: () => [] },
  rooms: { type: Array, default: () => [] },
  days: { type: Object, default: () => ({}) },
})

const page = usePage()
const { confirm } = useConfirm()
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))
const locked = computed(() => props.offering.semester?.status === 'completed')
const label = (value) => value.charAt(0).toUpperCase() + value.slice(1)
const statusOptions = computed(() => props.statuses.map((value) => ({ value, label: label(value) })))
const nextCode = computed(() => String.fromCharCode(65 + (props.offering.sections?.length ?? 0)))

const details = useForm({ status: props.offering.status, max_enrollments: props.offering.max_enrollments ?? '', notes: props.offering.notes ?? '' })
const section = useForm({ code: nextCode.value, name: '', capacity: 40 })

const saveDetails = () => details.put(`/offerings/${props.offering.id}`, { preserveScroll: true })
const addSection = () => section.post(`/offerings/${props.offering.id}/sections`, { preserveScroll: true, onSuccess: () => { section.reset(); section.code = nextCode.value } })
const destroy = async () => {
  if (await confirm({ title: 'Delete offering?', message: 'Only possible while it has no sections.', confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/offerings/${props.offering.id}`)
  }
}
</script>

<template>
  <Head :title="`${offering.course?.code} · ${offering.semester?.name} - EduCore`" />
  <div class="space-y-6">
    <PageHeader eyebrow="Offerings & sections" :title="`${offering.course?.code} — ${offering.course?.name}`" :description="`${offering.semester?.academic_year?.code ?? ''} · ${offering.semester?.name} · ${offering.course?.credits} credits`">
      <template #actions>
        <BaseButton href="/offerings" variant="secondary">Back to offerings</BaseButton>
      </template>
    </PageHeader>

    <div class="flex flex-wrap items-center gap-3">
      <StatusBadge :status="offering.status" />
      <span class="text-small text-muted dark:text-dark-muted">Semester {{ offering.semester?.status }}</span>
      <span v-if="locked" class="text-small text-warning">The semester is completed; classes can no longer change.</span>
    </div>

    <BaseCard v-if="canManage" title="Offering" padding="lg">
      <form class="grid gap-4 sm:grid-cols-[1fr_1fr_2fr_auto] sm:items-end" @submit.prevent="saveDetails">
        <BaseSelect v-model="details.status" label="Status" :options="statusOptions" :error="details.errors.status" />
        <BaseInput v-model="details.max_enrollments" name="max_enrollments" label="Max enrollments" type="number" min="1" placeholder="Optional" :error="details.errors.max_enrollments" />
        <BaseInput v-model="details.notes" name="notes" label="Notes" :error="details.errors.notes" />
        <BaseButton type="submit" :loading="details.processing">Save</BaseButton>
      </form>
      <div class="mt-4">
        <BaseButton size="sm" variant="ghost" class="text-error dark:text-red-300" @click="destroy">Delete offering</BaseButton>
      </div>
    </BaseCard>

    <section aria-labelledby="sections-heading" class="space-y-4">
      <h2 id="sections-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Sections</h2>
      <div v-if="offering.sections?.length" class="grid gap-4 lg:grid-cols-2">
        <SectionCard
          v-for="item in offering.sections"
          :key="item.id"
          :section="item"
          :lecturers="lecturers"
          :statuses="sectionStatuses"
          :roles="lecturerRoles"
          :rooms="rooms"
          :days="days"
          :can-manage="canManage && !locked"
        />
      </div>
      <BaseCard v-else><EmptyState title="No sections yet" description="Add section A to start assigning lecturers." /></BaseCard>

      <BaseCard v-if="canManage && !locked" title="Add a section" padding="lg">
        <form class="grid gap-4 sm:grid-cols-[1fr_2fr_1fr_auto] sm:items-end" @submit.prevent="addSection">
          <BaseInput v-model="section.code" name="section_code" label="Code" :error="section.errors.code" required />
          <BaseInput v-model="section.name" name="section_name" label="Name" placeholder="Optional, e.g. Morning" :error="section.errors.name" />
          <BaseInput v-model="section.capacity" name="section_capacity" label="Capacity" type="number" min="1" :error="section.errors.capacity" required />
          <BaseButton type="submit" :loading="section.processing">Add section</BaseButton>
        </form>
      </BaseCard>
    </section>
  </div>
</template>
