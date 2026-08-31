<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import {
  List,
  Plus,
  Package,
  PackageCheck,
  PackageX,
  AlertTriangle,
  Upload,
} from '@lucide/vue'

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['agregar-producto', 'ver-stock-bajo', 'carga-masiva'])

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
const lowStock = computed(() => props.stats.low_stock || 0)
const nuevas = computed(() => props.stats.new_this_month || 0)

const percent = (value) => {
  if (!total.value) return 0
  return Math.round((value / total.value) * 100)
}

const statusBars = computed(() => {
  const items = [
    { label: 'Activos', count: active.value, tone: 'low' },
    { label: 'Inactivos', count: inactive.value, tone: 'medium' },
    { label: 'Stock bajo', count: lowStock.value, tone: 'high' },
  ]

  return items.map((item) => ({
    ...item,
    widthPct: total.value && item.count
      ? Math.max(4, Math.round((item.count / total.value) * 100))
      : 0,
    barClass: item.tone === 'high'
      ? 'bg-[#FF3B30]'
      : item.tone === 'medium'
        ? 'bg-[#FF9500]'
        : 'bg-[#007AFF]',
  }))
})

const statusLegend = computed(() => [
  { label: 'Activos', value: active.value, pct: percent(active.value), dot: 'bg-[#007AFF]', valueClass: 'text-[#1D1D1F]' },
  { label: 'Inactivos', value: inactive.value, pct: percent(inactive.value), dot: 'bg-[#FF9500]', valueClass: 'text-[#1D1D1F]' },
  { label: 'Stock bajo', value: lowStock.value, pct: percent(lowStock.value), dot: 'bg-[#FF3B30]', valueClass: 'text-[#FF3B30]' },
])

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
  const height = 96
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

const metrics = computed(() => [
  {
    key: 'total',
    label: 'Productos',
    value: total.value,
    icon: Package,
    tone: 'text-[#007AFF] bg-[#E5F1FF]',
  },
  {
    key: 'active',
    label: 'Activos',
    value: active.value,
    icon: PackageCheck,
    tone: 'text-[#1D7A32] bg-[#E8F8E8]',
  },
  {
    key: 'inactive',
    label: 'Inactivos',
    value: inactive.value,
    icon: PackageX,
    tone: 'text-[#6E6E73] bg-[#F5F5F7]',
  },
  {
    key: 'low_stock',
    label: 'Stock bajo',
    value: lowStock.value,
    icon: AlertTriangle,
    tone: 'text-[#FF3B30] bg-[#FFE5E5]',
  },
])
</script>

<template>
  <section class="relative mb-6 overflow-hidden rounded-[28px] border border-[#E5E5E5] bg-white shadow-sm">
    <div
      class="pointer-events-none absolute inset-0 opacity-60"
      style="background-image: linear-gradient(rgba(0,122,255,0.035) 1px, transparent 1px), linear-gradient(90deg, rgba(0,122,255,0.035) 1px, transparent 1px); background-size: 28px 28px;"
    ></div>

    <div class="relative p-5 sm:p-6 lg:p-7 space-y-5">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
          <p class="text-[11px] tracking-[0.22em] uppercase text-[#007AFF]">Operaciones</p>
          <h2 class="mt-1 text-3xl sm:text-4xl font-display font-bold text-[#1D1D1F] tracking-tight">
            Inventario
          </h2>
          <p class="mt-1 text-sm text-[#8E8E93] capitalize">{{ dateLabel }}</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 self-start">
          <button
            type="button"
            @click="emit('carga-masiva')"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#007AFF]/40 bg-[#E5F1FF] px-4 py-2.5 text-sm font-semibold text-[#007AFF] hover:bg-[#D6E9FF] transition-colors"
          >
            <Upload class="w-4 h-4" />
            Carga masiva
          </button>
          <button
            type="button"
            @click="emit('agregar-producto')"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#007AFF] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0056CC] transition-colors"
          >
            <Plus class="w-4 h-4" />
            Agregar producto
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div
          v-for="metric in metrics"
          :key="metric.key"
          class="rounded-2xl border border-[#E5E5E5] bg-white/80 px-3.5 py-3 flex items-center gap-3"
        >
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl shrink-0" :class="metric.tone">
            <component :is="metric.icon" class="w-4 h-4" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] text-[#8E8E93] truncate">{{ metric.label }}</p>
            <p class="text-lg font-display font-bold text-[#1D1D1F] tabular-nums leading-tight">
              {{ metric.value }}
            </p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA]/80 p-4 sm:p-5">
          <div class="flex items-center justify-between gap-3 mb-4">
            <div>
              <p class="text-[11px] tracking-[0.18em] uppercase text-[#8E8E93]">Estado</p>
              <p class="text-sm font-semibold text-[#1D1D1F] mt-0.5">Distribución de productos</p>
            </div>
            <button
              type="button"
              @click="emit('ver-stock-bajo')"
              class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold bg-[#FFE5E5] text-[#FF3B30] hover:bg-[#FFD1D1] transition-colors shrink-0"
            >
              <List class="w-3.5 h-3.5" />
              Stock bajo
              <span class="tabular-nums">({{ lowStock }})</span>
            </button>
          </div>

          <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-5">
            <div class="flex-1 min-w-0 rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA] px-4 py-3">
              <div class="flex items-center justify-between gap-3 mb-3">
                <div class="min-w-0">
                  <p class="text-[10px] font-medium uppercase tracking-wide text-[#8E8E93]">Total inventario</p>
                  <p class="text-lg font-display font-bold text-[#1D1D1F] tabular-nums leading-tight">
                    {{ total }} productos
                  </p>
                </div>
                <div class="p-2 rounded-xl bg-gradient-to-br from-[#007AFF] to-[#0056CC] shadow-sm shrink-0">
                  <Package class="w-4 h-4 text-white" />
                </div>
              </div>

              <div class="space-y-2.5">
                <div
                  v-for="bar in statusBars"
                  :key="bar.label"
                  class="flex items-center gap-2.5 min-w-0"
                >
                  <span class="w-[4.5rem] shrink-0 text-[10px] text-[#8E8E93] truncate">{{ bar.label }}</span>
                  <div class="flex-1 h-2.5 bg-[#E5E5E5] rounded-full overflow-hidden min-w-0">
                    <div
                      class="h-full rounded-full transition-all"
                      :class="bar.barClass"
                      :style="{ width: `${bar.widthPct}%` }"
                    />
                  </div>
                  <span class="w-6 shrink-0 text-[11px] font-semibold tabular-nums text-[#1D1D1F] text-right">
                    {{ bar.count }}
                  </span>
                </div>
              </div>
            </div>

            <div class="lg:w-[9.5rem] shrink-0 grid grid-cols-3 lg:grid-cols-1 gap-2 lg:gap-2">
              <div
                v-for="item in statusLegend"
                :key="item.label"
                class="rounded-xl bg-white border px-2.5 py-2 text-center lg:text-right"
                :class="item.label === 'Stock bajo' ? 'border-[#FFE5E5]' : 'border-[#E5E5E5]'"
              >
                <div class="flex items-center justify-center lg:justify-end gap-1 mb-0.5">
                  <span class="h-1.5 w-1.5 rounded-full shrink-0" :class="item.dot"></span>
                  <span class="text-[10px] text-[#8E8E93] truncate">{{ item.label }}</span>
                </div>
                <p class="text-sm font-display font-bold tabular-nums leading-none" :class="item.valueClass">
                  {{ item.value }}
                </p>
                <p class="text-[10px] text-[#8E8E93] tabular-nums mt-0.5">{{ item.pct }}%</p>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA]/80 p-4 sm:p-5 flex flex-col">
          <div class="flex items-start justify-between gap-3 mb-1">
            <div>
              <p class="text-[11px] tracking-[0.18em] uppercase text-[#8E8E93]">Altas</p>
              <p class="text-sm font-semibold text-[#1D1D1F] mt-0.5">Productos registrados</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-[#E8F8E8] px-2.5 py-1 text-xs font-semibold text-[#1D7A32] tabular-nums">
              {{ altasDelta }} este mes
            </span>
          </div>
          <p class="text-xs text-[#8E8E93] mb-4">
            Acumulado en 12 meses:
            <span class="font-semibold text-[#1D1D1F] tabular-nums">{{ acumulado }}</span>
          </p>
          <div class="flex-1 flex flex-col justify-end">
            <svg :viewBox="`0 0 ${chart.width} ${chart.height}`" class="w-full h-24">
              <defs>
                <linearGradient id="inventoryAltasFill" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#007AFF" stop-opacity="0.28" />
                  <stop offset="100%" stop-color="#007AFF" stop-opacity="0" />
                </linearGradient>
              </defs>
              <line v-for="y in [18, 48, 78]" :key="y" x1="0" :y1="y" :x2="chart.width" :y2="y" stroke="#EFEFF4" />
              <polygon :points="chart.area" fill="url(#inventoryAltasFill)" />
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
    </div>
  </section>
</template>
