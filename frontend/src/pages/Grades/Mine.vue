<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Award, BookOpenCheck, GraduationCap } from '@lucide/vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'
import { gpa as formatGpa, percent } from '../../utils/grades'

const props = defineProps({
  grades: { type: Array, default: () => [] },
  gpa: { type: Object, required: true },
})

// Group approved grades by semester (already oldest first), newest semester on top.
const semesters = computed(() => {
  const groups = new Map()
  for (const grade of props.grades) {
    if (!groups.has(grade.semester_id)) groups.set(grade.semester_id, { id: grade.semester_id, label: grade.semester, grades: [] })
    groups.get(grade.semester_id).grades.push(grade)
  }
  return [...groups.values()].reverse().map((group) => ({ ...group, gpa: props.gpa.semesters.find((s) => s.semester_id === group.id) ?? null }))
})
const cumulative = computed(() => props.gpa.cumulative)
</script>

<template>
  <Head title="Grades & GPA - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Academics" title="Grades & GPA" description="Your approved course grades and credit-weighted GPA." />

    <div class="grid gap-4 sm:grid-cols-3">
      <StatCard label="Cumulative GPA" :value="formatGpa(cumulative?.gpa)" :icon="Award" detail="Latest attempt of each course" />
      <StatCard label="Credits earned" :value="cumulative ? cumulative.earned_credits : 0" :icon="GraduationCap" tone="success" :detail="cumulative ? `of ${cumulative.attempted_credits} attempted` : 'No graded courses yet'" />
      <StatCard label="Graded courses" :value="grades.length" :icon="BookOpenCheck" tone="muted" />
    </div>

    <BaseCard v-if="!grades.length">
      <EmptyState title="No grades yet" description="Grades appear here once your lecturer submits them and the university approves them." />
    </BaseCard>

    <BaseCard v-for="semester in semesters" v-else :key="semester.id" :title="semester.label" padding="lg">
      <template #description>
        <span v-if="semester.gpa">Semester GPA {{ formatGpa(semester.gpa.gpa) }} · {{ semester.gpa.earned_credits }} of {{ semester.gpa.attempted_credits }} credits earned</span>
      </template>
      <div class="-mx-2 overflow-x-auto">
        <table class="min-w-full">
          <caption class="sr-only">Grades for {{ semester.label }}</caption>
          <thead>
            <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
              <th scope="col" class="px-2 py-2">Course</th>
              <th scope="col" class="px-2 py-2 text-right">Credits</th>
              <th scope="col" class="px-2 py-2 text-right">Total</th>
              <th scope="col" class="px-2 py-2">Grade</th>
              <th scope="col" class="px-2 py-2 text-right">Points</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border-default dark:divide-dark-border">
            <tr v-for="grade in semester.grades" :key="grade.id" class="align-top">
              <td class="px-2 py-2">
                <p class="text-small font-medium text-ink dark:text-dark-ink">{{ grade.course.code }} · {{ grade.course.name }}</p>
                <p v-if="grade.remarks" class="text-caption text-muted dark:text-dark-muted">{{ grade.remarks }}</p>
              </td>
              <td class="px-2 py-2 text-right text-small tabular-nums">{{ grade.credits }}</td>
              <td class="px-2 py-2 text-right text-small tabular-nums">{{ percent(grade.total_score) }}</td>
              <td class="px-2 py-2 text-small font-semibold" :class="grade.grade_point > 0 ? 'text-ink dark:text-dark-ink' : 'text-error'">{{ grade.letter_grade }}</td>
              <td class="px-2 py-2 text-right text-small tabular-nums">{{ formatGpa(grade.grade_point) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </BaseCard>
  </div>
</template>
