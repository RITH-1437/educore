<script setup>
import { Building2, CircleCheck, GraduationCap, Presentation } from '@lucide/vue'
import { computed, ref } from 'vue'
import DashboardPreview from './DashboardPreview.vue'
import LandingTabs from './LandingTabs.vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'
import { previews } from './showcaseData'

defineProps({
  stats: { type: Object, default: null },
})

const tabs = [
  { key: 'admin', label: previews.admin.tab, short: 'Admin', icon: Building2 },
  { key: 'lecturer', label: previews.lecturer.tab, short: 'Lecturer', icon: Presentation },
  { key: 'student', label: previews.student.tab, short: 'Student', icon: GraduationCap },
]

const role = ref('admin')
const view = computed(() => previews[role.value])
</script>

<template>
  <section id="showcase" class="relative scroll-mt-20 overflow-hidden bg-primary-dark py-20 sm:py-24 dark:border-y dark:border-dark-border" aria-labelledby="showcase-title">
    <div class="landing-glow pointer-events-none absolute inset-0" aria-hidden="true" />
    <div class="bg-grid-dark pointer-events-none absolute inset-0" aria-hidden="true" />

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        id="showcase-title"
        dark
        eyebrow="See EduCore in action"
        title="Explore the Platform Experience"
        description="Every role signs in to a workspace built around its own work. Switch views to see what each one sees."
      />

      <Reveal class="mt-10">
        <LandingTabs v-model="role" :tabs="tabs" id-prefix="showcase" label="Workspace by role" dark />
      </Reveal>

      <Reveal class="mt-10" from="scale">
        <Transition name="edu-panel" mode="out-in">
          <div
            :id="`showcase-panel-${role}`"
            :key="role"
            role="tabpanel"
            :aria-labelledby="`showcase-tab-${role}`"
            tabindex="0"
            class="grid gap-8 rounded-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-dark-primary xl:grid-cols-[1fr_2fr] xl:items-center xl:gap-12"
          >
            <div class="md:grid md:grid-cols-2 md:gap-8 xl:block">
              <div>
                <h3 class="font-display text-h3 text-dark-ink">{{ view.title }}</h3>
                <p class="mt-3 text-body text-dark-muted">{{ view.summary }}</p>
              </div>
              <ul class="mt-6 space-y-3 md:mt-0 xl:mt-6">
                <li v-for="item in view.capabilities" :key="item" class="flex gap-3 text-small text-dark-ink">
                  <CircleCheck class="mt-0.5 h-4 w-4 shrink-0 text-secondary" aria-hidden="true" />
                  {{ item }}
                </li>
              </ul>
            </div>

            <figure class="min-w-0">
              <DashboardPreview :role="role" :stats="stats" />
              <figcaption class="mt-4 text-center text-caption text-dark-muted">{{ view.caption }}</figcaption>
            </figure>
          </div>
        </Transition>
      </Reveal>
    </div>
  </section>
</template>
