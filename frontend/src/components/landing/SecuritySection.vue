<script setup>
import { BadgeCheck, ClipboardList, KeyRound, ShieldCheck, UsersRound } from '@lucide/vue'
import Reveal from './Reveal.vue'

const items = [
  { icon: UsersRound, title: 'RBAC', text: 'Five roles; every page and API call checks the role.' },
  { icon: KeyRound, title: 'Secure authentication', text: 'Hashed passwords, rate-limited sign-in and password reset.' },
  { icon: ShieldCheck, title: 'Protected APIs', text: 'Sanctum tokens and authorization policies on every endpoint.' },
  { icon: ClipboardList, title: 'Audit logs', text: 'Who changed what, and when, with CSV export.' },
  { icon: BadgeCheck, title: 'Document verification', text: 'QR codes and SHA-256 checksums on issued PDFs.' },
]
</script>

<template>
  <section id="security" class="scroll-mt-24 bg-background py-16 sm:py-20 dark:bg-dark-bg" aria-labelledby="security-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <Reveal class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">Security</p>
          <h2 id="security-title" class="font-display mt-3 text-h2 text-primary-dark dark:text-dark-ink">Secure by design</h2>
        </div>
        <p class="max-w-md text-small text-muted dark:text-dark-muted">Academic records are sensitive. Access is checked on the server for every request.</p>
      </Reveal>

      <ul class="mt-10 grid gap-x-6 gap-y-8 sm:grid-cols-2 lg:grid-cols-5">
        <Reveal v-for="(item, i) in items" :key="item.title" as="li" :delay="i * 70" class="border-t border-border-default pt-5 dark:border-dark-border">
          <component :is="item.icon" class="h-5 w-5 text-primary dark:text-dark-primary" aria-hidden="true" />
          <h3 class="mt-3 text-small font-semibold text-primary-dark dark:text-dark-ink">{{ item.title }}</h3>
          <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ item.text }}</p>
        </Reveal>
      </ul>
    </div>
  </section>
</template>
