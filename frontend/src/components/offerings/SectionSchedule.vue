<script setup>
import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { X } from '@lucide/vue'
import BaseButton from '../BaseButton.vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'

// Weekly meetings of one section; conflicts are reported by the server under
// the field they concern (room, start time, …).
const props = defineProps({
  section: { type: Object, required: true },
  rooms: { type: Array, default: () => [] },
  days: { type: Object, default: () => ({}) },
  canManage: { type: Boolean, default: false },
})

const form = useForm({ day_of_week: 1, start_time: '08:00', end_time: '09:30', room_id: '' })
const dayOptions = computed(() => Object.entries(props.days).map(([value, label]) => ({ value: Number(value), label })))
const roomOptions = computed(() => props.rooms.map((room) => ({ value: room.id, label: room.label })))

const add = () => form.post(`/sections/${props.section.id}/schedule`, { preserveScroll: true, onSuccess: () => form.reset('room_id') })
const remove = (entry) => router.delete(`/schedule-entries/${entry.id}`, { preserveScroll: true })
</script>

<template>
  <div>
    <h4 class="text-small font-semibold text-ink dark:text-dark-ink">Weekly schedule</h4>
    <ul v-if="section.schedule?.length" class="mt-2 space-y-2">
      <li v-for="entry in section.schedule" :key="entry.id" class="flex items-center justify-between gap-2 text-small text-ink dark:text-dark-ink">
        <span><strong>{{ entry.day }}</strong> {{ entry.start_time }}–{{ entry.end_time }} · {{ entry.room?.code }}</span>
        <button v-if="canManage" type="button" class="inline-flex min-h-9 items-center rounded-md px-2 text-error hover:bg-error/5 focus-visible:outline-2 focus-visible:outline-error dark:text-red-300" :aria-label="`Remove ${entry.day} ${entry.start_time}`" @click="remove(entry)">
          <X class="h-4 w-4" aria-hidden="true" />
        </button>
      </li>
    </ul>
    <p v-else class="mt-2 text-small text-muted dark:text-dark-muted">No class times yet.</p>

    <form v-if="canManage" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_2fr_auto] lg:items-end" @submit.prevent="add">
      <BaseSelect v-model="form.day_of_week" label="Day" :options="dayOptions" :error="form.errors.day_of_week" />
      <BaseInput v-model="form.start_time" name="start_time" label="Start" type="time" :error="form.errors.start_time" />
      <BaseInput v-model="form.end_time" name="end_time" label="End" type="time" :error="form.errors.end_time" />
      <BaseSelect v-model="form.room_id" label="Room" :options="roomOptions" placeholder="Select a room" :error="form.errors.room_id" />
      <BaseButton type="submit" size="md" :disabled="!form.room_id" :loading="form.processing">Add time</BaseButton>
    </form>
  </div>
</template>
