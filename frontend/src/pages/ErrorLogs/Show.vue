<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { AlertCircle, ArrowLeft, ChevronRight, FileText, MessageSquare, Search, Server, User, UserX } from '@lucide/vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'

const props = defineProps({
  errorLog: { type: Object, required: true },
})

const formatDate = (dateString) => {
  if (!dateString) return '—'
  return new Date(dateString).toLocaleString(undefined, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    timeZoneName: 'short',
  })
}

const getStatusBadgeVariant = (code) => {
  if (code === 404) return 'warning'
  if (code >= 500) return 'error'
  return 'muted'
}

const getStatusLabel = (code) => {
  if (code === 404) return 'Not Found'
  if (code === 500) return 'Internal Server Error'
  if (code === 502) return 'Bad Gateway'
  if (code === 503) return 'Service Unavailable'
  if (code >= 500) return 'Server Error'
  return 'Unknown'
}

const back = () => router.get('/error-logs', {}, { preserveState: true })
</script>

<template>
  <Head :title="`Error #${errorLog.id} - EduCore`" />
  <div class="space-y-6">
    <header class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <BaseButton variant="ghost" size="sm" @click="back">
          <ArrowLeft class="h-4 w-4" aria-hidden="true" />
        </BaseButton>
        <div>
          <p class="text-caption font-semibold uppercase tracking-widest text-primary">Diagnostics</p>
          <h1 class="text-h1 font-display font-semibold text-ink dark:text-dark-ink">Error #{{ errorLog.id }}</h1>
        </div>
      </div>
      <BaseBadge :variant="getStatusBadgeVariant(errorLog.status_code)" dot size="md">
        {{ getStatusLabel(errorLog.status_code) }} ({{ errorLog.status_code }})
      </BaseBadge>
    </header>

    <div class="grid gap-6 lg:grid-cols-3">
      <BaseCard class="lg:col-span-2" padding="lg">
        <h3 class="mb-4 text-h3 font-semibold text-ink dark:text-dark-ink">Request Details</h3>
        <dl class="space-y-4 text-sm">
          <div class="grid gap-1 sm:grid-cols-[max-content_1fr]">
            <dt class="font-medium text-muted dark:text-dark-muted">HTTP Method</dt>
            <dd class="font-mono text-ink dark:text-dark-ink">
              <BaseBadge variant="muted" size="sm">{{ errorLog.method }}</BaseBadge>
            </dd>
          </div>
          <div class="grid gap-1 sm:grid-cols-[max-content_1fr]">
            <dt class="font-medium text-muted dark:text-dark-muted">Request Path</dt>
            <dd class="font-mono text-ink dark:text-dark-ink break-all">{{ errorLog.url }}</dd>
          </div>
          <div class="grid gap-1 sm:grid-cols-[max-content_1fr]">
            <dt class="font-medium text-muted dark:text-dark-muted">Route Name</dt>
            <dd v-if="errorLog.route_name" class="font-mono text-ink dark:text-dark-ink">{{ errorLog.route_name }}</dd>
            <dd v-else class="text-muted dark:text-dark-muted">—</dd>
          </div>
          <div class="grid gap-1 sm:grid-cols-[max-content_1fr]">
            <dt class="font-medium text-muted dark:text-dark-muted">Occurred</dt>
            <dd class="font-mono text-ink dark:text-dark-ink">{{ formatDate(errorLog.created_at) }}</dd>
          </div>
          <div class="grid gap-1 sm:grid-cols-[max-content_1fr]">
            <dt class="font-medium text-muted dark:text-dark-muted">Client IP</dt>
            <dd v-if="errorLog.ip_address" class="font-mono text-ink dark:text-dark-ink">{{ errorLog.ip_address }}</dd>
            <dd v-else class="text-muted dark:text-dark-muted">—</dd>
          </div>
          <div class="grid gap-1 sm:grid-cols-[max-content_1fr]">
            <dt class="font-medium text-muted dark:text-dark-muted">User Agent</dt>
            <dd v-if="errorLog.user_agent" class="font-mono text-xs text-muted dark:text-dark-muted break-all">{{ errorLog.user_agent }}</dd>
            <dd v-else class="text-muted dark:text-dark-muted">—</dd>
          </div>
        </dl>
      </BaseCard>

      <BaseCard padding="lg">
        <h3 class="mb-4 text-h3 font-semibold text-ink dark:text-dark-ink">User</h3>
        <div v-if="errorLog.user" class="space-y-3">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
              <User class="h-5 w-5 text-primary" aria-hidden="true" />
            </div>
            <div>
              <p class="font-medium text-ink dark:text-dark-ink">{{ errorLog.user.name }}</p>
              <p class="text-sm text-muted dark:text-dark-muted">{{ errorLog.user.email }}</p>
            </div>
          </div>
          <p class="text-caption text-muted dark:text-dark-muted">ID: {{ errorLog.user.id }}</p>
        </div>
        <div v-else class="flex items-center gap-3 text-center py-4">
          <div class="h-10 w-10 rounded-full bg-muted/10 flex items-center justify-center mx-auto">
            <UserX class="h-5 w-5 text-muted" aria-hidden="true" />
          </div>
          <p class="text-muted dark:text-dark-muted">Anonymous request</p>
        </div>
      </BaseCard>
    </div>

    <div v-if="errorLog.exception_class || errorLog.message" class="grid gap-6 lg:grid-cols-2">
      <BaseCard padding="lg">
        <h3 class="mb-4 text-h3 font-semibold text-ink dark:text-dark-ink flex items-center gap-2">
          <AlertCircle class="h-5 w-5 text-error" aria-hidden="true" />
          Exception
        </h3>
        <div class="space-y-4 text-sm">
          <div v-if="errorLog.exception_class">
            <p class="font-medium text-muted dark:text-dark-muted">Class</p>
            <p class="font-mono text-ink dark:text-dark-ink break-all">{{ errorLog.exception_class }}</p>
          </div>
          <div v-if="errorLog.message">
            <p class="font-medium text-muted dark:text-dark-muted">Message</p>
            <p class="font-mono text-ink dark:text-dark-ink whitespace-pre-wrap break-all">{{ errorLog.message }}</p>
          </div>
          <div v-else class="text-muted dark:text-dark-muted">No message captured.</div>
        </div>
      </BaseCard>

      <BaseCard v-if="errorLog.context" padding="lg">
        <h3 class="mb-4 text-h3 font-semibold text-ink dark:text-dark-ink flex items-center gap-2">
          <Search class="h-5 w-5 text-primary" aria-hidden="true" />
          Context
        </h3>
        <pre class="font-mono text-sm text-ink dark:text-dark-ink bg-background p-4 rounded-lg overflow-auto dark:bg-dark-surface">
          {{ JSON.stringify(errorLog.context, null, 2) }}
        </pre>
      </BaseCard>
    </div>

    <BaseCard padding="lg" class="border-border-default dark:border-dark-border">
      <h3 class="mb-4 text-h3 font-semibold text-ink dark:text-dark-ink">Notes</h3>
      <ul class="space-y-2 text-sm text-muted dark:text-dark-muted">
        <li class="flex items-start gap-2">
          <FileText class="h-4 w-4 flex-shrink-0" aria-hidden="true" />
          Query strings are never stored — only the request path.
        </li>
        <li class="flex items-start gap-2">
          <Server class="h-4 w-4 flex-shrink-0" aria-hidden="true" />
          Only HTTP 404 and 5xx responses are recorded. 401, 403, 409, 422 are considered normal control flow.
        </li>
        <li class="flex items-start gap-2">
          <MessageSquare class="h-4 w-4 flex-shrink-0" aria-hidden="true" />
          Exception messages may contain internal details — this screen is Super Admin only.
        </li>
      </ul>
    </BaseCard>
  </div>
</template>