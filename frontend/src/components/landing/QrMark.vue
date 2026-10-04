<script setup>
// Decorative QR-style mark for the sample document: three finder patterns and
// a fixed pseudo-random module field. It encodes nothing (the real QR codes are
// generated server-side on issued PDFs).
const SIZE = 25
const finders = [
  [0, 0],
  [SIZE - 7, 0],
  [0, SIZE - 7],
]
const inFinder = (x, y) => finders.some(([fx, fy]) => x >= fx - 1 && x <= fx + 7 && y >= fy - 1 && y <= fy + 7)

let seed = 11
const next = () => {
  seed = (seed * 9301 + 49297) % 233280
  return seed / 233280
}

const modules = []
for (let y = 0; y < SIZE; y++) {
  for (let x = 0; x < SIZE; x++) {
    if (!inFinder(x, y) && next() > 0.5) modules.push({ x, y })
  }
}
</script>

<template>
  <svg :viewBox="`0 0 ${SIZE} ${SIZE}`" shape-rendering="crispEdges" fill="currentColor" aria-hidden="true">
    <rect v-for="m in modules" :key="`${m.x}-${m.y}`" :x="m.x" :y="m.y" width="1" height="1" />
    <g v-for="[fx, fy] in finders" :key="`${fx}-${fy}`">
      <rect :x="fx + 0.5" :y="fy + 0.5" width="6" height="6" fill="none" stroke="currentColor" stroke-width="1" />
      <rect :x="fx + 2" :y="fy + 2" width="3" height="3" />
    </g>
  </svg>
</template>
