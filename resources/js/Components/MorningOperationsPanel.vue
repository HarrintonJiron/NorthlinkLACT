<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { AlertTriangle, DollarSign, Droplets, MapPin, Package, Users } from '@lucide/vue'

const props = defineProps({
  overview: { type: Object, required: true },
  userName: { type: String, required: true },
})

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

const numberFormatter = new Intl.NumberFormat('es-NI', { maximumFractionDigits: 2 })
const currencyFormatter = new Intl.NumberFormat('es-NI', {
  style: 'currency',
  currency: 'NIO',
  maximumFractionDigits: 2,
})

const formattedDateTime = computed(() =>
  now.value.toLocaleString('es-NI', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
)

const greeting = computed(() => {
  const hour = now.value.getHours()
  if (hour < 12) return 'Buenos días'
  if (hour < 18) return 'Buenas tardes'
  return 'Buenas noches'
})

const producersProgress = computed(() => {
  if (props.overview.producers_scheduled === 0) return 0
  return Math.min(100, Math.round(
    (props.overview.producers_attended / props.overview.producers_scheduled) * 100
  ))
})

const financeTotal = computed(() => props.overview.finance.income + props.overview.finance.outflow)
const financeIncomePercentage = computed(() => {
  if (financeTotal.value === 0) return 0
  return Math.round((props.overview.finance.income / financeTotal.value) * 100)
})

const inventoryHealthy = computed(() =>
  Math.max(0, props.overview.inventory.active - props.overview.inventory.low_stock)
)
const inventoryHealthyPercentage = computed(() => {
  if (props.overview.inventory.active === 0) return 0
  return Math.round((inventoryHealthy.value / props.overview.inventory.active) * 100)
})

const summaryLine = computed(() =>
  `${numberFormatter.format(props.overview.liters_today)} L acopiados hoy · ` +
  `${props.overview.producers_attended} productores atendidos · ` +
  `${props.overview.routes_completed}/${props.overview.routes_total} rutas completadas · ` +
  `${props.overview.alerts.total} alertas por revisar.`
)

const trendLabel = computed(() => {
  const trend = props.overview.liters_trend_percent
  if (trend === null) return 'Sin base de comparación con ayer'
  const prefix = trend > 0 ? '↑' : trend < 0 ? '↓' : '→'
  return `${prefix} ${Math.abs(trend)}% respecto a ayer`
})

const trendClass = computed(() =>
  props.overview.liters_trend_percent !== null && props.overview.liters_trend_percent < 0
    ? 'text-[#FF9500]'
    : 'text-[#34C759]'
)

const sparklinePoints = computed(() => {
  const values = props.overview.liters_trend.length ? props.overview.liters_trend : [0, 0]
  const max = Math.max(...values)
  const min = Math.min(...values)
  const range = max - min || 1

  return values.map((value, index) => {
    const x = values.length === 1 ? 60 : (index / (values.length - 1)) * 120
    const y = 36 - ((value - min) / range) * 32 - 2
    return `${x},${y}`
  }).join(' ')
})

const sparklineArea = computed(() => {
  const points = sparklinePoints.value.split(' ')
  const first = points[0].split(',')
  const last = points[points.length - 1].split(',')
  return `M ${first[0]},36 L ${points.join(' L ')} L ${last[0]},36 Z`
})

const donutSegments = computed(() => {
  const segments = [
    { value: props.overview.routes_completed, color: '#34C759', label: 'Completadas' },
    { value: props.overview.routes_in_progress, color: '#007AFF', label: 'En proceso' },
    { value: props.overview.routes_pending, color: '#E5E5E5', label: 'Pendientes' },
  ]
  const total = segments.reduce((sum, segment) => sum + segment.value, 0) || 1
  const circumference = 2 * Math.PI * 28
  let offset = 0

  return segments.map((segment) => {
    const dash = (segment.value / total) * circumference
    const current = { ...segment, dash, offset }
    offset += dash
    return current
  })
})

const alertToneClass = {
  high: 'bg-[#FFE5E5] text-[#FF3B30]',
  medium: 'bg-[#FFF4E5] text-[#FF9500]',
  low: 'bg-[#E5F1FF] text-[#007AFF]',
}
</script>

<template>
  <section class="mb-6 overflow-hidden rounded-[28px] border border-[#E5E5E5] bg-white shadow-sm">
    <div class="border-b border-[#E5E5E5] bg-gradient-to-br from-[#F5F5F7] via-white to-[#E5F1FF]/30 px-6 pb-5 pt-6">
      <p class="flex items-center gap-2 text-[11px] uppercase tracking-[0.22em] text-[#007AFF]">
        <span class="inline-block h-0.5 w-6 rounded-full bg-[#007AFF]" />
        Mesa de operaciones
      </p>
      <p class="mt-2 text-sm capitalize text-[#8E8E93]">{{ formattedDateTime }}</p>
      <h1 class="mt-1 font-display text-3xl font-bold text-[#1D1D1F] md:text-4xl">
        {{ greeting }}, {{ userName }}
      </h1>
      <p class="mt-2 max-w-4xl text-sm text-[#6E6E73]">{{ summaryLine }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-3">
      <article class="rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA] p-4 transition-colors hover:border-[#007AFF]/30">
        <div class="mb-3 flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-medium text-[#8E8E93]">Litros acopiados hoy</p>
            <p class="mt-0.5 font-display text-2xl font-bold text-[#1D1D1F]">{{ numberFormatter.format(overview.liters_today) }} L</p>
            <p class="mt-0.5 text-xs text-[#8E8E93]">Ayer: {{ numberFormatter.format(overview.liters_yesterday) }} L</p>
          </div>
          <div class="rounded-xl bg-gradient-to-br from-[#007AFF] to-[#5AC8FA] p-2.5 shadow-sm">
            <Droplets class="size-5 text-white" />
          </div>
        </div>
        <div class="flex items-end justify-between gap-3">
          <p class="text-xs font-semibold" :class="trendClass">{{ trendLabel }}</p>
          <svg viewBox="0 0 120 36" class="h-9 w-28 shrink-0" aria-hidden="true">
            <path :d="sparklineArea" fill="#007AFF" fill-opacity="0.12" />
            <polyline :points="sparklinePoints" fill="none" stroke="#007AFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
      </article>

      <article class="rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA] p-4 transition-colors hover:border-[#34C759]/30">
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1">
            <p class="text-xs font-medium text-[#8E8E93]">Productores atendidos</p>
            <p class="mt-0.5 font-display text-2xl font-bold text-[#1D1D1F]">{{ overview.producers_attended }}/{{ overview.producers_scheduled }}</p>
            <p class="mt-0.5 text-xs text-[#8E8E93]">{{ Math.max(0, overview.producers_scheduled - overview.producers_attended) }} pendientes hoy</p>
          </div>
          <div class="relative size-16 shrink-0">
            <svg viewBox="0 0 72 72" class="size-16 -rotate-90">
              <circle cx="36" cy="36" r="28" fill="none" stroke="#E5E5E5" stroke-width="8" />
              <circle cx="36" cy="36" r="28" fill="none" stroke="#34C759" stroke-width="8" stroke-linecap="round" :stroke-dasharray="`${producersProgress * 1.76} 176`" />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-[#1D1D1F]">{{ producersProgress }}%</span>
          </div>
        </div>
        <div class="mt-3 flex items-center gap-2">
          <div class="rounded-xl bg-gradient-to-br from-[#34C759] to-[#30D158] p-2"><Users class="size-4 text-white" /></div>
          <p class="text-xs font-semibold text-[#34C759]">Cobertura real del día</p>
        </div>
      </article>

      <article class="rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA] p-4 transition-colors hover:border-[#FF9500]/30">
        <div class="mb-3 flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-medium text-[#8E8E93]">Estado de rutas</p>
            <p class="mt-0.5 font-display text-2xl font-bold text-[#1D1D1F]">{{ overview.routes_completed }}/{{ overview.routes_total }}</p>
            <p class="mt-0.5 text-xs text-[#8E8E93]">{{ overview.routes_in_progress }} en proceso</p>
          </div>
          <div class="rounded-xl bg-gradient-to-br from-[#FF9500] to-[#FF9F0A] p-2.5 shadow-sm"><MapPin class="size-5 text-white" /></div>
        </div>
        <div class="flex items-center gap-4">
          <svg viewBox="0 0 72 72" class="size-16 shrink-0 -rotate-90">
            <circle v-for="segment in donutSegments" :key="segment.label" cx="36" cy="36" r="28" fill="none" :stroke="segment.color" stroke-width="10" :stroke-dasharray="`${segment.dash} ${176 - segment.dash}`" :stroke-dashoffset="-segment.offset" />
          </svg>
          <div class="flex flex-col gap-1 text-xs">
            <p v-for="segment in donutSegments" :key="segment.label" class="flex items-center gap-2 text-[#6E6E73]">
              <span class="size-2 rounded-full" :style="{ backgroundColor: segment.color }" />
              {{ segment.label }} · {{ segment.value }}
            </p>
          </div>
        </div>
      </article>

      <article class="rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA] p-4 transition-colors hover:border-[#AF52DE]/30">
        <div class="mb-4 flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-medium text-[#8E8E93]">Finanzas de la semana</p>
            <p class="mt-0.5 font-display text-2xl font-bold text-[#1D1D1F]">{{ currencyFormatter.format(overview.finance.income) }}</p>
            <p class="mt-0.5 text-xs text-[#8E8E93]">Salidas: {{ currencyFormatter.format(overview.finance.outflow) }}</p>
          </div>
          <div class="rounded-xl bg-gradient-to-br from-[#AF52DE] to-[#BF5AF2] p-2.5 shadow-sm"><DollarSign class="size-5 text-white" /></div>
        </div>
        <div class="flex flex-col gap-2">
          <div class="flex justify-between text-xs">
            <span class="text-[#6E6E73]">{{ overview.finance.movements }} movimientos</span>
            <span class="font-semibold text-[#1D1D1F]">{{ financeIncomePercentage }}% ingresos</span>
          </div>
          <div class="h-2 overflow-hidden rounded-full bg-[#E5E5E5]"><div class="h-full rounded-full bg-[#AF52DE]" :style="{ width: `${financeIncomePercentage}%` }" /></div>
        </div>
      </article>

      <article class="rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA] p-4 transition-colors hover:border-[#34C759]/30">
        <div class="mb-3 flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-medium text-[#8E8E93]">Inventario activo</p>
            <p class="mt-0.5 font-display text-2xl font-bold text-[#1D1D1F]">{{ overview.inventory.active }}</p>
            <p class="mt-0.5 text-xs text-[#8E8E93]">{{ overview.inventory.low_stock }} con existencias bajas</p>
          </div>
          <div class="rounded-xl bg-gradient-to-br from-[#34C759] to-[#30D158] p-2.5 shadow-sm"><Package class="size-5 text-white" /></div>
        </div>
        <div class="flex flex-col gap-2">
          <div class="flex justify-between text-xs">
            <span class="text-[#6E6E73]">{{ inventoryHealthy }} productos saludables</span>
            <span class="font-semibold text-[#1D1D1F]">{{ inventoryHealthyPercentage }}%</span>
          </div>
          <div class="h-2 overflow-hidden rounded-full bg-[#E5E5E5]"><div class="h-full rounded-full bg-[#34C759]" :style="{ width: `${inventoryHealthyPercentage}%` }" /></div>
          <p class="text-xs text-[#FF3B30]">{{ overview.inventory.zero_stock }} sin existencias</p>
        </div>
      </article>

      <article class="rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA] p-4 transition-colors hover:border-[#FF3B30]/30">
        <div class="mb-3 flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-medium text-[#8E8E93]">Alertas operativas</p>
            <p class="mt-0.5 font-display text-2xl font-bold text-[#1D1D1F]">{{ overview.alerts.total }}</p>
            <p class="mt-0.5 text-xs text-[#8E8E93]">calculadas con datos actuales</p>
          </div>
          <div class="rounded-xl bg-gradient-to-br from-[#FF3B30] to-[#FF453A] p-2.5 shadow-sm"><AlertTriangle class="size-5 text-white" /></div>
        </div>
        <div v-if="overview.alerts.items.length" class="flex flex-wrap gap-2">
          <span v-for="alert in overview.alerts.items" :key="alert.label" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold" :class="alertToneClass[alert.tone]">
            {{ alert.count }} {{ alert.label.toLowerCase() }}
          </span>
        </div>
        <p v-else class="text-sm font-medium text-[#34C759]">Sin alertas pendientes</p>
      </article>
    </div>
  </section>
</template>
