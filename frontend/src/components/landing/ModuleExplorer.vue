<script setup>
import { CircleCheck } from '@lucide/vue'
import { computed, ref } from 'vue'
import BaseBadge from '../BaseBadge.vue'
import LandingTabs from './LandingTabs.vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'
import { moduleGroups } from './moduleData'

const active = ref(moduleGroups[0].key)
const current = computed(() => moduleGroups.find((group) => group.key === active.value))
</script>

<template>
  <section id="modules" class="scroll-mt-20 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="modules-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        id="modules-title"
        eyebrow="Platform modules"
        title="Everything Connected in One Platform"
        description="EduCore's modules fall into four areas. Choose one to see what it covers, who uses it, and what is not part of EduCore yet."
      />

      <div class="mt-12 grid gap-8 lg:grid-cols-[18rem_1fr] lg:gap-12">
        <Reveal from="left" class="min-w-0 lg:self-start">
          <LandingTabs v-model="active" :tabs="moduleGroups" id-prefix="modules" label="Module areas" vertical />
        </Reveal>

        <Transition name="edu-panel" mode="out-in">
          <div
            :id="`modules-panel-${current.key}`"
            :key="current.key"
            role="tabpanel"
            :aria-labelledby="`modules-tab-${current.key}`"
            tabindex="0"
            class="min-w-0 rounded-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary dark:focus-visible:outline-dark-primary"
          >
            <div class="flex flex-col gap-4 border-b border-border-default pb-6 sm:flex-row sm:items-start sm:justify-between dark:border-dark-border">
              <div class="max-w-xl">
                <h3 class="font-display text-h3 text-primary-dark dark:text-dark-ink">{{ current.label }}</h3>
                <p class="mt-2 text-small text-muted dark:text-dark-muted">{{ current.summary }}</p>
              </div>
              <BaseBadge variant="success" size="md" class="shrink-0">
                <CircleCheck class="h-4 w-4" aria-hidden="true" />
                {{ current.modules.length }} modules available now
              </BaseBadge>
            </div>

            <p class="mt-4 flex flex-wrap items-center gap-2 text-caption text-muted dark:text-dark-muted">
              <span class="font-semibold tracking-widest uppercase">Used by</span>
              <span
                v-for="role in current.roles"
                :key="role"
                class="rounded-pill border border-border-default px-3 py-1 font-medium text-ink dark:border-dark-border dark:text-dark-ink"
              >
                {{ role }}
              </span>
            </p>

            <ul class="mt-4 grid gap-x-8 sm:grid-cols-2">
              <li v-for="m in current.modules" :key="m.title" class="flex items-start gap-3 border-t border-border-default py-4 first:border-t-0 sm:[&:nth-child(2)]:border-t-0 dark:border-dark-border">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
                  <component :is="m.icon" class="h-4 w-4" aria-hidden="true" />
                </span>
                <div>
                  <h4 class="text-small font-semibold text-primary-dark dark:text-dark-ink">{{ m.title }}</h4>
                  <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ m.text }}</p>
                </div>
              </li>
            </ul>

            <div v-if="current.later.length" class="mt-4 rounded-lg border border-dashed border-border-muted p-4 dark:border-dark-border">
              <p class="text-caption font-semibold tracking-widest text-muted uppercase dark:text-dark-muted">Not part of EduCore yet</p>
              <ul class="mt-3 space-y-3">
                <li v-for="item in current.later" :key="item.title" class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
                  <BaseBadge variant="muted" class="shrink-0">{{ item.status }}</BaseBadge>
                  <p class="text-small text-muted dark:text-dark-muted">
                    <span class="font-semibold text-ink dark:text-dark-ink">{{ item.title }}</span> — {{ item.text }}
                  </p>
                </li>
              </ul>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </section>
</template>
