<script setup>
import IconButton from '../../components/IconButton.vue'
import { Eye, Scale } from '@lucide/vue'
import { Head, router } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseTable from '../../components/BaseTable.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  sections: { type: Object, required: true },
  status: { type: String, default: 'submitted' },
  canApprove: { type: Boolean, default: false },
})

const show = (status) => router.get('/grades', { status: status === 'submitted' ? undefined : status }, { preserveState: true, replace: true })

const columns = [
  { key: 'course', label: 'Course / section' },
  { key: 'semester', label: 'Semester' },
  { key: 'counts', label: 'Grades' },
  { key: 'actions', label: 'Actions', align: 'right' },
]
</script>

<template>
  <Head title="Grades - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Academics" title="Grades" :description="canApprove ? 'Review submitted section grades and approve them so they count toward GPA.' : 'Section grades submitted by lecturers.'">
      <template #actions>
        <IconButton :icon="Scale" href="/grading-scale" size="md" label="Grading scale" />
      </template>
    </PageHeader>

    <div class="flex gap-2" role="group" aria-label="Filter sections">
      <BaseButton size="sm" :variant="status === 'submitted' ? 'primary' : 'ghost'" :aria-pressed="status === 'submitted'" @click="show('submitted')">Awaiting approval</BaseButton>
      <BaseButton size="sm" :variant="status === 'all' ? 'primary' : 'ghost'" :aria-pressed="status === 'all'" @click="show('all')">All graded sections</BaseButton>
    </div>

    <BaseTable
      :columns="columns"
      :rows="sections.data"
      caption="Sections with grades"
      :empty-title="status === 'submitted' ? 'Nothing awaiting approval' : 'No grades yet'"
      empty-description="Sections appear here once a lecturer computes grades."
    >
      <template #cell-course="{ row }">
        <p class="font-medium">{{ row.course.code }} · {{ row.code }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ row.course.name }}</p>
      </template>
      <template #cell-semester="{ row }">{{ row.semester }}</template>
      <template #cell-counts="{ row }">
        <div class="flex flex-wrap gap-1">
          <StatusBadge v-if="row.counts.submitted" status="pending" :label="`${row.counts.submitted} awaiting approval`" />
          <StatusBadge v-if="row.counts.approved" status="approved" :label="`${row.counts.approved} approved`" />
          <StatusBadge v-if="row.counts.finalized" status="finalized" :label="`${row.counts.finalized} finalized`" />
          <StatusBadge v-if="row.counts.draft" status="draft" :label="`${row.counts.draft} draft`" />
        </div>
      </template>
      <template #cell-actions="{ row }">
        <IconButton :icon="Eye" :href="`/grades/sections/${row.id}`" label="Open grade sheet" />
      </template>
    </BaseTable>

    <Pagination :links="sections.links" />
  </div>
</template>
