<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['nueva-ruta'])

const now = ref(new Date())
let timer = null

onMounted(() => {
  timer = setInterval(() => {
    now.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const dateLabel = computed(() =>
  now.value.toLocaleDateString('es-NI', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  })
)

const total = computed(() => props.stats.total || 0)
const active = computed(() => props.stats.active || 0)
const inactive = computed(() => props.stats.inactive || 0)
const nuevas = computed(() => props.stats.new_this_month || 0)

const percent = (value) => {
  if (!total.value) return 0
  return Math.round((value / total.value) * 100)
}

const circumference = 2 * Math.PI * 42
const activeDash = computed(() => (percent(active.value) / 100) * circumference)
const inactiveDash = computed(() => (percent(inactive.value) / 100) * circumference)

const monthly = computed(() => props.stats.monthly || [])
const altasSeries = computed(() => {
  let running = 0
  return monthly.value.map((month) => {
    running += month.count
    return running
  })
})

const altasDelta = computed(() => {
  const value = nuevas.value
  return value > 0 ? `+${value}` : `${value}`
})

const acumulado = computed(() => {
  if (!altasSeries.value.length) return 0
  return altasSeries.value[altasSeries.value.length - 1]
})

const chart = computed(() => {
  const values = altasSeries.value.length ? altasSeries.value : [0]
  const width = 260
  const height = 110
  const max = Math.max(...values, 1)
  const step = values.length > 1 ? width / (values.length - 1) : width
  const points = values.map((value, index) => {
    const x = index * step
    const y = height - (value / max) * (height - 8) - 4
    return `${x},${y}`
  })
  const line = points.join(' ')
  const area = `0,${height} ${line} ${width},${height}`
  const labels = [
    monthly.value[0]?.label,
    monthly.value[Math.floor(monthly.value.length / 2)]?.label,
    monthly.value[monthly.value.length - 1]?.label,
  ]
    .filter(Boolean)
    .map((label) => String(label).toUpperCase())

  return { width, height, line, area, labels }
})
</script>

<template>
  <section class="relative mb-6 overflow-hidden rounded-[28px] border border-[#E5E5E5] bg-white shadow-sm">
    <div
      class="pointer-events-none absolute inset-0 opacity-70"
      style="background-image: linear-gradient(rgba(0,122,255,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(0,122,255,0.04) 1px, transparent 1px); background-size: 28px 28px;"
    ></div>
    <div class="pointer-events-none absolute -left-10 bottom-0 h-40 w-56 opacity-15">
      <svg viewBox="0 0 200 140" fill="none" class="h-full w-full">
        <path d="M20 120 L50 70 L80 90 L110 40 L140 75 L180 20" stroke="#8E8E93" stroke-width="1.2" />
        <path d="M30 120 V85 H55 V120 M70 120 V60 H95 V120 M120 120 V70 H150 V120" stroke="#8E8E93" stroke-width="1" />
      </svg>
    </div>

    <div class="relative grid grid-cols-1 lg:grid-cols-12 gap-6 p-6 lg:p-7">
      <div class="lg:col-span-5 flex flex-col justify-between min-h-[220px]">
        <div>
          <p class="text-[11px] tracking-[0.22em] uppercase text-[#007AFF]">— Acopio</p>
          <p class="mt-3 text-sm text-[#6E6E73] capitalize">{{ dateLabel }}</p>
          <h2 class="mt-1 text-4xl lg:text-5xl font-display font-bold text-[#1D1D1F] tracking-tight">Rutas</h2>
          <p class="mt-3 text-sm text-[#8E8E93]">
            {{ total }} rutas · {{ active }} activas · {{ inactive }} inactivas · {{ nuevas }} nuevas
          </p>
        </div>
        <div class="mt-6">
          <button
            type="button"
            @click="emit('nueva-ruta')"
            class="rounded-2xl border border-[#007AFF]/40 bg-[#E5F1FF] px-5 py-3 text-left hover:bg-[#D6E9FF] transition-colors"
          >
            <p class="text-sm font-semibold text-[#1D1D1F]">Nueva ruta</p>
            <p class="text-[11px] text-[#6E6E73]">Registrar expediente</p>
          </button>
        </div>
      </div>

      <div class="lg:col-span-3 flex flex-col">
        <div class="flex items-center justify-between mb-3">
          <p class="text-[11px] tracking-[0.2em] uppercase text-[#007AFF]">Estado</p>
          <p class="text-sm text-[#007AFF] tabular-nums">{{ total }}</p>
        </div>
        <div class="flex-1 flex items-center justify-center">
          <div class="relative h-36 w-36">
            <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
              <circle cx="60" cy="60" r="42" fill="none" stroke="#E5E5E5" stroke-width="12" />
              <circle
                cx="60"
                cy="60"
                r="42"
                fill="none"
                stroke="#007AFF"
                stroke-width="12"
                stroke-linecap="round"
                :stroke-dasharray="`${activeDash} ${circumference}`"
              />
              <circle
                cx="60"
                cy="60"
                r="42"
                fill="none"
                stroke="#8E8E93"
                stroke-width="12"
                stroke-linecap="round"
                :stroke-dasharray="`${inactiveDash} ${circumference}`"
                :stroke-dashoffset="-activeDash"
              />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <p class="text-[10px] tracking-[0.18em] text-[#8E8E93]">TOTAL</p>
              <p class="text-2xl font-display font-bold text-[#1D1D1F] tabular-nums">{{ total }}</p>
            </div>
          </div>
        </div>
        <div class="mt-3 space-y-1.5 text-xs">
          <div class="grid grid-cols-[auto_1fr_auto_auto] gap-x-3 items-center text-[#1D1D1F]">
            <span class="h-2 w-2 rounded-full bg-[#007AFF]"></span>
            <span>Activas</span>
            <span class="tabular-nums">{{ active }}</span>
            <span class="text-[#8E8E93] w-10 text-right">{{ percent(active) }}%</span>
          </div>
          <div class="grid grid-cols-[auto_1fr_auto_auto] gap-x-3 items-center text-[#1D1D1F]">
            <span class="h-2 w-2 rounded-full bg-[#8E8E93]"></span>
            <span>Inactivas</span>
            <span class="tabular-nums">{{ inactive }}</span>
            <span class="text-[#8E8E93] w-10 text-right">{{ percent(inactive) }}%</span>
          </div>
          <div class="grid grid-cols-[auto_1fr_auto_auto] gap-x-3 items-center text-[#1D1D1F]">
            <span class="h-2 w-2 rounded-full bg-[#FF3B30]"></span>
            <span>Nuevas</span>
            <span class="tabular-nums">{{ nuevas }}</span>
            <span class="text-[#8E8E93] w-10 text-right">{{ percent(nuevas) }}%</span>
          </div>
        </div>
      </div>

      <div class="lg:col-span-4 flex flex-col">
        <div class="flex items-center justify-between mb-1">
          <p class="text-[11px] tracking-[0.2em] uppercase text-[#007AFF]">Altas</p>
          <p class="text-sm font-semibold text-[#34C759] tabular-nums">{{ altasDelta }}</p>
        </div>
        <p class="text-xs text-[#8E8E93] mb-3">
          Acumulado {{ acumulado }} · {{ nuevas }} este mes
        </p>
        <div class="flex-1 flex flex-col justify-end">
          <svg :viewBox="`0 0 ${chart.width} ${chart.height}`" class="w-full h-28">
            <defs>
              <linearGradient id="altasFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#007AFF" stop-opacity="0.28" />
                <stop offset="100%" stop-color="#007AFF" stop-opacity="0" />
              </linearGradient>
            </defs>
            <line v-for="y in [20, 50, 80]" :key="y" x1="0" :y1="y" :x2="chart.width" :y2="y" stroke="#EFEFF4" />
            <polygon :points="chart.area" fill="url(#altasFill)" />
            <polyline
              :points="chart.line"
              fill="none"
              stroke="#007AFF"
              stroke-width="2.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          <div class="mt-2 flex justify-between text-[10px] tracking-wider text-[#8E8E93] uppercase">
            <span v-for="label in chart.labels" :key="label">{{ label }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
