<script setup>
import { FileLock2, KeyRound, ScrollText, ServerCog, UsersRound } from '@lucide/vue'
import Reveal from './Reveal.vue'

// Controls that exist in the code (reports 28, 29, 32, 39, 50, 52) — described,
// not certified.
const items = [
  { icon: KeyRound, title: 'Authentication', text: 'Hashed passwords, rate-limited sign-in, emailed password reset and optional two-factor codes from an authenticator app.' },
  { icon: UsersRound, title: 'Role-based access control', text: 'Five roles. Pages and actions are checked on the server, and Department Admins see only their department.' },
  { icon: ServerCog, title: 'Protected API endpoints', text: 'Sanctum tokens and authorization policies on the API; requests without the right role are refused.' },
  { icon: ScrollText, title: 'Audit logging', text: 'Sign-ins and sensitive changes are recorded with before and after values, append-only in the database.' },
  { icon: FileLock2, title: 'Controlled document access', text: 'Files are stored privately and downloaded through authorized routes; public verification shows only minimal facts.' },
]
</script>

<template>
  <section id="security" class="scroll-mt-20 bg-background py-20 sm:py-24 dark:bg-dark-bg" aria-labelledby="security-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <Reveal class="grid gap-6 lg:grid-cols-2 lg:items-end lg:gap-16">
        <div>
          <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">Security & access</p>
          <h2 id="security-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">
            Designed with Access Control in Mind
          </h2>
        </div>
        <div>
          <p class="text-body text-muted dark:text-dark-muted">
            Academic and financial records are sensitive. EduCore decides on the server what each person can see and do,
            for every page and request — hiding a button is never the only protection.
          </p>
          <p class="mt-3 text-caption text-muted dark:text-dark-muted">
            These controls are part of EduCore's code; EduCore has not been independently audited or certified against a security standard.
          </p>
        </div>
      </Reveal>

      <ul class="mt-12 grid gap-x-6 gap-y-8 sm:grid-cols-2 lg:grid-cols-5">
        <Reveal v-for="(item, i) in items" :key="item.title" as="li" :delay="i * 70" class="border-t-2 border-primary pt-5 dark:border-dark-primary">
          <component :is="item.icon" class="h-5 w-5 text-primary dark:text-dark-primary" aria-hidden="true" />
          <h3 class="mt-3 text-small font-semibold text-primary-dark dark:text-dark-ink">{{ item.title }}</h3>
          <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ item.text }}</p>
        </Reveal>
      </ul>
    </div>
  </section>
</template>
