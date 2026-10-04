<script setup>
import { Building2, GraduationCap, UserRound } from '@lucide/vue'
import { computed, ref } from 'vue'
import DashboardPreview from './DashboardPreview.vue'
import LandingTabs from './LandingTabs.vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'
import { previews } from './showcaseData'

const tabs = [
  { key: 'admin', label: 'Admin', icon: Building2 },
  { key: 'lecturer', label: 'Lecturer', icon: UserRound },
  { key: 'student', label: 'Student', icon: GraduationCap },
]

defineProps({
  stats: { type: Object, default: null },
})

const role = ref('admin')
const view = computed(() => previews[role.value])
</script>

<template>
  <section id="showcase" class="relative scroll-mt-24 overflow-hidden bg-primary-dark py-20 sm:py-24 dark:border-y dark:border-dark-border" aria-labelledby="showcase-title">
    <div class="landing-glow pointer-events-none absolute inset-0" aria-hidden="true" />
    <div class="bg-grid-dark pointer-events-none absolute inset-0" aria-hidden="true" />

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        id="showcase-title"
        dark
        eyebrow="See EduCore in action"
        title="A workspace for every role"
        description="Each person signs in to a dashboard built around their own work. Switch roles to see what they see."
      />

      <Reveal class="mt-10">
        <LandingTabs v-model="role" :tabs="tabs" id-prefix="showcase" label="Dashboard by role" dark />
      </Reveal>

      <Reveal class="mt-8" from="scale">
        <Transition name="edu-panel" mode="out-in">
          <figure
            :id="`showcase-panel-${role}`"
            :key="role"
            role="tabpanel"
            :aria-labelledby="`showcase-tab-${role}`"
            tabindex="0"
            class="mx-auto max-w-5xl rounded-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-dark-primary"
          >
            <p class="mx-auto mb-6 max-w-2xl text-center text-body text-dark-muted">{{ view.summary }}</p>
            <DashboardPreview :role="role" :stats="stats" />
            <figcaption class="mt-4 text-center text-caption text-dark-muted">
              <template v-if="role === 'admin'">Live figures from this installation · the same numbers as the University Admin dashboard</template>
              <template v-else>Layout of the real {{ view.eyebrow.toLowerCase() }} · no personal data is shown here</template>
            </figcaption>
          </figure>
        </Transition>
      </Reveal>
    </div>
  </section>
</template>
