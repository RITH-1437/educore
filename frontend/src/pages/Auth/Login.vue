<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'

const form = useForm({ email: '', password: '', remember: false })
const showPassword = ref(false)

const itcLogoUrl = '/assets/images/schools/itc-logo-card.png'
const backgroundUrl = '/assets/images/schools/itc-win2-bg.jpg'

const submit = () => form.post('/login', { preserveScroll: true })

const hasAuthError = computed(() => Object.keys(form.errors).length > 0)
</script>

<script>
import GuestLayout from '../../layouts/GuestLayout.vue'

export default { layout: GuestLayout }
</script>

<template>
  <Head title="Sign in" />

  <main
    class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-900 px-4 py-12 sm:px-6"
  >
    <div
      class="absolute inset-0 bg-cover bg-center"
      :style="{ backgroundImage: `url(${backgroundUrl})` }"
      aria-hidden="true"
    />
    <div class="absolute inset-0 bg-slate-900/75" aria-hidden="true" />

    <div class="relative w-full max-w-[440px]">
      <div class="rounded-2xl border border-white/20 bg-white/15 p-6 shadow-2xl backdrop-blur-xs sm:p-8">
        <img
          :src="itcLogoUrl"
          alt="ITC logo"
          class="mx-auto h-20 w-20 rounded-full object-contain"
        />

        <h1 class="mt-6 text-center text-2xl font-semibold tracking-tight text-white">
          ITC Win Win
        </h1>
        <p class="mt-2 text-center text-sm text-slate-200">
          Access your Institute of Technology of Cambodia securely.
        </p>

        <div
          v-if="hasAuthError"
          class="mt-6 flex items-start gap-3 rounded-lg border border-red-400/40 bg-red-500/15 p-4 backdrop-blur-md"
          role="alert"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="mt-0.5 h-5 w-5 shrink-0 text-red-200">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
          </svg>
          <div>
            <p class="text-sm font-semibold text-red-100">Unable to sign in</p>
            <p class="mt-0.5 text-sm text-red-200">
              Please check your credentials and try again.
            </p>
          </div>
        </div>

        <form class="mt-8" @submit.prevent="submit">
          <BaseInput
            v-model="form.email"
            label="Email"
            type="email"
            placeholder="you@university.edu"
            autocomplete="email"
            autofocus
            variant="glass"
            :error="form.errors.email"
          >
            <template #leading>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
              </svg>
            </template>
          </BaseInput>

          <BaseInput
            v-model="form.password"
            label="Password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Your password"
            autocomplete="current-password"
            class="mt-4"
            variant="glass"
            :error="form.errors.password"
          >
            <template #leading>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
              </svg>
            </template>
            <template #trailing>
              <button
                type="button"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                :aria-pressed="showPassword"
                :title="showPassword ? 'Hide password' : 'Show password'"
                class="rounded-md p-2 text-white/70 transition-colors duration-150 hover:text-white focus-visible:ring-2 focus-visible:ring-blue-300/40 focus-visible:outline-none"
                @click="showPassword = !showPassword"
              >
                <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19 12 19c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 5c4.477 0 8.268 2.943 9.542 7a10.44 10.44 0 0 1-2.25 3.841m-5.091-4.5a3 3 0 0 1-4.158-4.158M3 3l18 18" />
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
              </button>
            </template>
          </BaseInput>

          <div class="mt-3 flex items-center">
            <label class="flex cursor-pointer items-center">
<input
                  v-model="form.remember"
                  type="checkbox"
                  class="h-4 w-4 rounded border-white/30 accent-blue-400 focus:ring-2 focus:ring-blue-300/40 focus:ring-offset-0"
                />
                <span class="ml-2 text-sm text-slate-100 select-none">
                  Remember me
                </span>
            </label>
          </div>

          <BaseButton type="submit" size="lg" :loading="form.processing" class="mt-6 w-full">
            <span v-if="form.processing">Signing in…</span>
            <span v-else>Sign in</span>
          </BaseButton>

<p class="mt-5 flex items-center justify-center gap-1.5 text-xs text-white/60">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-3.5 w-3.5 shrink-0" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
            <span>Protected with CSRF tokens and login rate limiting.</span>
          </p>
        </form>
      </div>

      <p class="mt-6 flex items-center justify-center gap-1 text-center text-sm text-slate-200 dark:text-slate-400">
        <span>Back to</span>
        <Link href="/" class="font-medium text-blue-400 hover:text-blue-300">
          the EduCore home page
        </Link>
      </p>
    </div>
  </main>
</template>