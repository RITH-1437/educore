<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import Chart from 'chart.js/auto'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

const barRef = ref(null)
const lineRef = ref(null)
let barChart = null
let lineChart = null

onMounted(() => {
  barChart = new Chart(barRef.value, {
    type: 'bar',
    data: {
      labels: ['Engineering', 'IT', 'Business', 'Medicine', 'Education', 'Arts'],
      datasets: [
        {
          label: 'Enrolled students',
          data: [1240, 982, 866, 512, 420, 308],
          backgroundColor: '#2563eb',
          hoverBackgroundColor: '#1d4ed8',
          borderRadius: 6,
          maxBarThickness: 30,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(148, 163, 184, 0.18)' },
          ticks: { color: '#64748b', font: { size: 11 } },
        },
        x: {
          grid: { display: false },
          ticks: { color: '#64748b', font: { size: 11 } },
        },
      },
    },
  })

  lineChart = new Chart(lineRef.value, {
    type: 'line',
    data: {
      labels: ['Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'],
      datasets: [
        {
          label: 'Attendance rate',
          data: [92, 94, 93, 95, 94, 96, 95, 96],
          borderColor: '#14b8a6',
          backgroundColor: 'rgba(20, 184, 166, 0.12)',
          fill: true,
          tension: 0.35,
          pointBackgroundColor: '#14b8a6',
          pointRadius: 3,
          borderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (ctx) => `${ctx.parsed.y}%`,
          },
        },
      },
      scales: {
        y: {
          min: 80,
          max: 100,
          grid: { color: 'rgba(148, 163, 184, 0.18)' },
          ticks: { color: '#64748b', font: { size: 11 }, callback: (v) => `${v}%` },
        },
        x: {
          grid: { display: false },
          ticks: { color: '#64748b', font: { size: 11 } },
        },
      },
    },
  })
})

onBeforeUnmount(() => {
  barChart?.destroy()
  lineChart?.destroy()
})
</script>

<template>
  <section id="analytics" class="scroll-mt-24 bg-white py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        eyebrow="Analytics"
        title="Turn University Data Into Insight"
        description="Enrollment, attendance, performance, course statistics, and administrative activity can be understood at a glance — for staff who need the full picture."
      />

      <div class="mt-14 grid gap-6 lg:grid-cols-2">
        <Reveal class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-display text-sm font-semibold text-slate-900">Enrollment by Faculty</h3>
              <p class="text-xs text-slate-500">Illustrative demo data</p>
            </div>
            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">Students</span>
          </div>
          <div class="mt-5 h-64">
            <canvas ref="barRef" />
          </div>
        </Reveal>

        <Reveal :delay="120" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-display text-sm font-semibold text-slate-900">Attendance Trend</h3>
              <p class="text-xs text-slate-500">Illustrative demo data</p>
            </div>
            <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[11px] font-semibold text-teal-700">2025 / 26</span>
          </div>
          <div class="mt-5 h-64">
            <canvas ref="lineRef" />
          </div>
        </Reveal>
      </div>

      <Reveal :delay="200" class="mt-6 flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
        <span class="mt-0.5 rounded bg-slate-900 px-1.5 py-0.5 text-[10px] font-bold text-white">Note</span>
        <p class="text-sm text-slate-600">
          Charts above are illustrative and not real institutional statistics. They demonstrate how
          EduCore analytics reports would present data.
        </p>
      </Reveal>
    </div>
  </section>
</template>