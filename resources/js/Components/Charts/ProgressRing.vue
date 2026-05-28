<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  value: number
  max: number
  size?: number
  strokeWidth?: number
  color?: string
}>()

const size = props.size ?? 80
const sw = props.strokeWidth ?? 6
const color = props.color ?? '#1DF412'
const radius = (size - sw) / 2
const circumference = 2 * Math.PI * radius

const pct = computed(() => Math.min(100, Math.max(0, (props.value / props.max) * 100)))
const offset = computed(() => circumference - (pct.value / 100) * circumference)
const cx = size / 2
const cy = size / 2
</script>

<template>
  <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`" style="transform:rotate(-90deg);">
    <circle
      :cx="cx" :cy="cy" :r="radius"
      fill="none" stroke="#242424"
      :stroke-width="sw"
    />
    <circle
      :cx="cx" :cy="cy" :r="radius"
      fill="none" :stroke="color"
      :stroke-width="sw"
      stroke-linecap="round"
      :stroke-dasharray="circumference"
      :stroke-dashoffset="offset"
      :style="{ transition: 'stroke-dashoffset 0.6s ease' }"
    />
  </svg>
</template>
