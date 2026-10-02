<script setup>
// The sign-in page's frosted card on the campus background, shared by the
// password reset pages so the guest flow looks like one place.
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})

const itcLogoUrl = '/assets/images/schools/itc-logo-card.png'
const backgroundUrl = '/assets/images/schools/itc-win2-bg.jpg'
</script>

<template>
  <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-primary-dark px-4 py-12 sm:px-6">
    <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${backgroundUrl})` }" aria-hidden="true" />
    <div class="absolute inset-0 bg-primary-dark/70" aria-hidden="true" />
    <div class="absolute -top-24 -left-24 h-80 w-80 rounded-pill bg-primary/40 blur-3xl" aria-hidden="true" />
    <div class="absolute -right-24 -bottom-24 h-80 w-80 rounded-pill bg-accent/25 blur-3xl" aria-hidden="true" />

    <div class="relative w-full max-w-[440px] motion-safe:animate-slide-up">
      <div class="glass-frosted rounded-xl border p-6 sm:p-8">
        <img :src="itcLogoUrl" alt="ITC logo" width="80" height="80" class="mx-auto h-20 w-20 rounded-pill object-contain" />
        <h1 class="mt-6 text-center text-h4 font-semibold tracking-tight text-white sm:text-h3">{{ title }}</h1>
        <p v-if="subtitle" class="mt-2 text-center text-small text-white/80">{{ subtitle }}</p>
        <slot />
      </div>
    </div>
  </div>
</template>
