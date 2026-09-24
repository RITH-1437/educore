<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'

const form = useForm({ email: '', password: '', remember: false })
const showPassword = ref(false)

const symbolMarkUrl = '/assets/logo/educore-symbol-mark.jpg'
const primaryLogoUrl = '/assets/logo/educore-primary.png'
const workmarkLogoUrl = '/assets/logo/educore-workmark.png'
const submit = () => form.post('/login', { preserveScroll: true })

const hasAuthError = computed(() => Object.keys(form.errors).length > 0)
</script>

<script>
import GuestLayout from '../../layouts/GuestLayout.vue'

export default { layout: GuestLayout }
</script>

<template>
  <div class="min-h-screen lg:grid lg:grid-cols-2">
    <!-- Brand panel (desktop) -->
    <aside
      class="relative hidden overflow-hidden bg-slate-900 lg:flex lg:flex-col lg:justify-between lg:p-12"
    >
      <div
        class="pointer-events-none absolute -top-24 -left-24 h-96 w-96 rounded-full bg-blue-600/25 blur-3xl"
      />
      <div
        class="pointer-events-none absolute right-0 bottom-0 h-80 w-80 translate-x-1/3 translate-y-1/3 rounded-full bg-blue-400/10 blur-3xl"
      />

      <div class="relative z-10">
        <img
          :src="primaryLogoUrl"
          alt="EduCore primary logo"
          class="h-10 w-auto object-contain"
        />
      </div>

      <div class="relative max-w-md">
        <h2 class="text-3xl leading-tight font-bold tracking-tight text-white">
          Manage your university, all in one secure portal.
        </h2>
        <p class="mt-4 text-slate-300">
          One sign-in for administrators, faculty, and students — backed by role-based
          access and full audit trails.
        </p>

        <ul class="mt-10 space-y-5">
          <li class="flex items-center gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-blue-300">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
              </svg>
            </span>
            <span class="text-sm text-slate-200">Role-based access control on every page</span>
          </li>
          <li class="flex items-center gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-blue-300">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
              </svg>
            </span>
            <span class="text-sm text-slate-200">Session and API authentication built in</span>
          </li>
          <li class="flex items-center gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-blue-300">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15m0-3a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 3H9" />
              </svg>
            </span>
            <span class="text-sm text-slate-200">Protected by CSRF, throttling, and audit logs</span>
          </li>
        </ul>
      </div>

      <p class="relative text-sm text-slate-400/80">
        © 2026 EduCore · University Digital Administration Platform
      </p>
    </aside>

    <!-- Form panel -->
    <main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12 sm:px-6">
      <div class="w-full max-w-[440px]">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-700 dark:bg-slate-900">
          <img
            :src="symbolMarkUrl"
            alt="EduCore symbol mark"
            class="mx-auto h-12 w-12 object-contain"
          />

          <h1 class="mt-6 text-center text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">
            Sign in to EduCore
          </h1>
          <p class="mt-2 text-center text-sm text-slate-500 dark:text-slate-400">
            Access your university workspace securely.
          </p>

          <div
            v-if="hasAuthError"
            class="mt-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4"
            role="alert"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="mt-0.5 h-5 w-5 shrink-0 text-red-600">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <div>
              <p class="text-sm font-semibold text-red-800">Unable to sign in</p>
              <p class="mt-0.5 text-sm text-red-600">
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
                  class="rounded-md p-2 text-slate-500 transition-colors duration-150 hover:text-slate-700 focus-visible:ring-2 focus-visible:ring-blue-600/40 focus-visible:outline-none dark:text-slate-400 dark:hover:text-slate-200"
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
                  class="h-4 w-4 rounded border-slate-300 accent-blue-600 focus:ring-2 focus:ring-blue-600/25 focus:ring-offset-0 dark:border-slate-600 dark:accent-blue-400"
                />
                <span class="ml-2 text-sm text-slate-600 select-none dark:text-slate-300">
                  Remember me
                </span>
              </label>
            </div>

            <BaseButton type="submit" size="lg" :loading="form.processing" class="mt-6 w-full">
              <span v-if="form.processing">Signing in…</span>
              <span v-else>Sign in</span>
            </BaseButton>
          </form>
        </div>

        <p class="mt-6 flex items-center justify-center gap-1 text-center text-sm text-slate-500 dark:text-slate-400">
          <span>Back to</span>
          <Link href="/" class="font-medium text-blue-600 hover:text-blue-700">
            the EduCore home page
          </Link>
        </p>
      </div>
    </main>
  </div>
</template>