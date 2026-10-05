<script setup>
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import MaterialList from '../../components/coursework/MaterialList.vue'

// Course materials of the student's current sections (report 44), grouped by course.
const props = defineProps({
  materials: { type: Array, default: () => [] },
})

const course = ref('')
const courses = computed(() => {
  const groups = new Map()
  for (const material of props.materials) {
    const key = `${material.course.code}-${material.course.section}`
    if (!groups.has(key)) groups.set(key, { key, ...material.course, items: [] })
    groups.get(key).items.push(material)
  }
  return [...groups.values()].sort((a, b) => a.code.localeCompare(b.code))
})
const courseOptions = computed(() => courses.value.map((group) => ({ value: group.key, label: `${group.code} · ${group.name}` })))
const shown = computed(() => courses.value.filter((group) => !course.value || group.key === course.value))
</script>

<template>
  <Head title="Course materials - EduCore" />
  <div class="mx-auto max-w-4xl space-y-6">
    <PageHeader eyebrow="Learning" title="Course materials" description="Slides, handouts and links your lecturers share with the sections you are enrolled in." />

    <BaseCard v-if="!materials.length">
      <EmptyState title="No materials yet" description="When a lecturer of one of your sections shares something, it appears here and in your notifications." />
    </BaseCard>

    <template v-else>
      <div v-if="courses.length > 1" class="max-w-xs">
        <BaseSelect v-model="course" label="Course" :options="courseOptions" placeholder="All courses" />
      </div>

      <BaseCard v-for="group in shown" :key="group.key" padding="lg">
        <template #header>
          <h2 class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ group.code }} · {{ group.name }}</h2>
          <p class="mt-1 text-small text-muted dark:text-dark-muted">Section {{ group.section }} · {{ group.items.length }} {{ group.items.length === 1 ? 'material' : 'materials' }}</p>
        </template>
        <MaterialList :materials="group.items" />
      </BaseCard>
    </template>
  </div>
</template>
