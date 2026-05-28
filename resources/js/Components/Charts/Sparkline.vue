<script setup lang="ts">
interface DataPoint {
  value: number
  date: string
}

const props = defineProps<{
  data: DataPoint[]
  color?: string
  height?: number
}>()

const color = props.color ?? '#1DF412'
const height = props.height ?? 60

const path = $computed(() => {
  if (!props.data || props.data.length < 2) return ''
  const values = props.data.map(d => d.value)
  const min = Math.min(...values)
  const max = Math.max(...values)
  const range = max - min || 1
  const w = 200
  const h = height
  const pad = 4

  const points = props.data.map((d, i) => {
    const x = (i / (props.data.length - 1)) * (w - pad * 2) + pad
    const y = h - ((d.value - min) / range) * (h - pad * 2) - pad
    return [x, y]
  })

  const pathData = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' ')
  return pathData
})

const areaPath = $computed(() => {
  if (!props.data || props.data.length < 2) return ''
  const values = props.data.map(d => d.value)
  const min = Math.min(...values)
  const max = Math.max(...values)
  const range = max - min || 1
  const w = 200
  const h = height
  const pad = 4

  const points = props.data.map((d, i) => {
    const x = (i / (props.data.length - 1)) * (w - pad * 2) + pad
    const y = h - ((d.value - min) / range) * (h - pad * 2) - pad
    return [x, y]
  })

  const line = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' ')
  const lastX = points[points.length - 1][0].toFixed(1)
  const firstX = points[0][0].toFixed(1)
  return `${line} L ${lastX} ${h} L ${firstX} ${h} Z`
})

const lastPoint = $computed(() => {
  if (!props.data || props.data.length === 0) return null
  const last = props.data[props.data.length - 1]
  const values = props.data.map(d => d.value)
  const min = Math.min(...values)
  const max = Math.max(...values)
  const range = max - min || 1
  const w = 200
  const h = height
  const pad = 4
  const x = w - pad
  const y = h - ((last.value - min) / range) * (h - pad * 2) - pad
  return { x: x.toFixed(1), y: y.toFixed(1), value: last.value }
})
</script>

<template>
  <svg :viewBox="`0 0 200 ${height}`" preserveAspectRatio="none" style="width:100%;height:${height}px;">
    <defs>
      <linearGradient :id="`grad-${color.replace('#','')}`" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" :stop-color="color" stop-opacity="0.3" />
        <stop offset="100%" :stop-color="color" stop-opacity="0" />
      </linearGradient>
    </defs>
    <path v-if="areaPath" :d="areaPath" :fill="`url(#grad-${color.replace('#','')})`" />
    <path v-if="path" :d="path" :stroke="color" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    <circle v-if="lastPoint" :cx="lastPoint.x" :cy="lastPoint.y" r="4" :fill="color" />
  </svg>
</template>
