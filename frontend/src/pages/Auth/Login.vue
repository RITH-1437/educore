<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { CircleAlert, CircleCheck, Eye, EyeOff, Lock, Mail, ShieldCheck } from '@lucide/vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseInput from '../../components/BaseInput.vue'

defineProps({
  canResetPassword: { type: Boolean, default: false },
  status: { type: String, default: null },
})

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

  <!-- GuestLayout already provides the <main> landmark. -->
  <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-primary-dark px-4 py-12 sm:px-6">
    <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${backgroundUrl})` }" aria-hidden="true" />
    <div class="absolute inset-0 bg-primary-dark/70" aria-hidden="true" />
    <div class="absolute -top-24 -left-24 h-80 w-80 rounded-pill bg-primary/40 blur-3xl" aria-hidden="true" />
    <div class="absolute -right-24 -bottom-24 h-80 w-80 rounded-pill bg-accent/25 blur-3xl" aria-hidden="true" />

    <div class="relative w-full max-w-[440px] motion-safe:animate-slide-up">
      <div class="glass-frosted rounded-xl border p-6 sm:p-8">
        <img :src="itcLogoUrl" alt="ITC logo" width="80" height="80" class="mx-auto h-20 w-20 rounded-pill object-contain" />

        <h1 class="mt-6 text-center text-h4 font-semibold tracking-tight text-white sm:text-h3">ITC Win Win</h1>
        <p class="mt-2 text-center text-small text-white/80">Access your Institute of Technology of Cambodia securely.</p>

        <div v-if="status && !hasAuthError" class="mt-6 flex items-start gap-3 rounded-lg border border-green-300/40 bg-success/15 p-4" role="status">
          <CircleCheck class="mt-0.5 h-5 w-5 shrink-0 text-green-200" aria-hidden="true" />
          <p class="text-small text-green-100">{{ status }}</p>
        </div>

        <div
          v-if="hasAuthError"
          class="mt-6 flex items-start gap-3 rounded-lg border border-red-300/40 bg-error/15 p-4 motion-safe:animate-slide-down"
          role="alert"
        >
          <CircleAlert class="mt-0.5 h-5 w-5 shrink-0 text-red-200" aria-hidden="true" />
          <div>
            <p class="text-small font-semibold text-red-100">Unable to sign in</p>
            <p class="mt-0.5 text-small text-red-100/90">Please check your credentials and try again.</p>
          </div>
        </div>

        <form class="mt-8" @submit.prevent="submit">
          <BaseInput
            v-model="form.email"
            name="email"
            label="Email"
            type="email"
            placeholder="you@university.edu"
            autocomplete="email"
            autofocus
            required
            variant="glass"
            :error="form.errors.email"
          >
            <template #leading><Mail class="h-5 w-5" aria-hidden="true" /></template>
          </BaseInput>

          <BaseInput
            v-model="form.password"
            name="password"
            label="Password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Your password"
            autocomplete="current-password"
            class="mt-4"
            required
            variant="glass"
            :error="form.errors.password"
          >
            <template #leading><Lock class="h-5 w-5" aria-hidden="true" /></template>
            <template #trailing>
              <button
                type="button"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                :aria-pressed="showPassword"
                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-white/75 transition-colors duration-150 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-dark-primary"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" class="h-5 w-5" aria-hidden="true" />
                <Eye v-else class="h-5 w-5" aria-hidden="true" />
              </button>
            </template>
          </BaseInput>

          <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <label class="flex min-h-11 w-fit cursor-pointer items-center gap-2">
              <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded-sm border-white/30 accent-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-dark-primary" />
              <span class="select-none text-small text-white/90">Remember me</span>
            </label>
            <Link v-if="canResetPassword" href="/forgot-password" class="rounded-sm text-small font-medium text-dark-primary underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-dark-primary">Forgot password?</Link>
          </div>

          <BaseButton type="submit" size="lg" :loading="form.processing" class="mt-4 w-full hover:border-primary! hover:bg-primary/85!">
            {{ form.processing ? 'Signing in…' : 'Sign in' }}
          </BaseButton>

          <p class="mt-5 flex items-center justify-center gap-1.5 text-caption text-white/70">
            <ShieldCheck class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            <span>Protected with CSRF tokens and login rate limiting.</span>
          </p>
        </form>
      </div>

      <p class="mt-6 flex items-center justify-center gap-1 text-center text-small text-white/80">
        <span>Back to</span>
        <Link href="/" class="rounded-sm font-medium text-dark-primary underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-dark-primary">the EduCore home page</Link>
      </p>
    </div>
  </div>
</template>
