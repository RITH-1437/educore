<script setup>
import { BadgeCheck, ClipboardList, Fingerprint, KeyRound, ShieldCheck, Users } from '@lucide/vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

const items = [
  { icon: Users, title: 'RBAC', text: 'Five institutional roles, each scoped to the workflows they manage.' },
  { icon: Fingerprint, title: 'Secure Authentication', text: 'Session and token-based access with rate-limited sign in.' },
  { icon: ClipboardList, title: 'Audit Logs', text: 'Activity trails designed to record who did what, and when.' },
  { icon: ShieldCheck, title: 'Protected APIs', text: 'REST endpoints guarded by authentication and authorization.' },
  { icon: KeyRound, title: 'Role-Based Access', text: 'Super Admin, Accountant, Registrar, Lecturer, and Student scopes.' },
  { icon: BadgeCheck, title: 'Document Verification', text: 'Records designed to be checked before they are trusted.' },
]
</script>

<template>
  <section id="security" class="scroll-mt-24 relative overflow-hidden bg-slate-950 py-20 sm:py-28">
    <div class="bg-grid-dark absolute inset-0" aria-hidden="true" />
    <div class="absolute -bottom-24 left-1/2 h-72 w-[760px] -translate-x-1/2 rounded-full bg-blue-600/15 blur-3xl" aria-hidden="true" />

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        dark
        eyebrow="Security"
        title="Built With Security in Mind"
        description="University systems manage sensitive academic and institutional information. EduCore is designed with role-based access, secure authentication, audit logging, and controlled data access as core principles."
      />

      <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Reveal
          v-for="(item, i) in items"
          :key="item.title"
          :delay="(i % 3) * 110"
          class="rounded-xl border border-white/10 bg-white/5 p-6 transition duration-300 hover:border-white/20 hover:bg-white/[0.07]"
        >
          <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-teal-500/15">
            <component :is="item.icon" class="h-5 w-5 text-teal-400" />
          </span>
          <h3 class="font-display mt-4 text-base font-semibold text-white">{{ item.title }}</h3>
          <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ item.text }}</p>
        </Reveal>
      </div>
    </div>
  </section>
</template>