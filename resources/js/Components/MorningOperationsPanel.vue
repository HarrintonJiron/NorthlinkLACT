<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import {
  Droplets,
  Users,
  MapPin,
  DollarSign,
  Package,
  AlertTriangle,
} from '@lucide/vue'

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

const litersToday = 12450
const litersTarget = 15000
const litersTrend = [8200, 9100, 8800, 10200, 12450]
const litersProgress = computed(() => Math.min(100, Math.round((litersToday / litersTarget) * 100)))

const producersAttended = 87
const producersScheduled = 95
const producersProgress = computed(() => Math.round((producersAttended / producersScheduled) * 100))

const routesCompleted = 8
const routesTotal = 12
const routesInProgress = 4
const routesPending = routesTotal - routesCompleted - routesInProgress

const paymentsWeek = 450000
const paymentsPending = 38000
const paymentsPaidPct = computed(() =>
  Math.round((paymentsWeek / (paymentsWeek + paymentsPending)) * 100)
)

const productionBreakdown = [
  { label: 'Queso', liters: 5200, color: '#007AFF' },
  { label: 'Crema', liters: 3400, color: '#5AC8FA' },
  { label: 'Suero', liters: 2600, color: '#34C759' },
]
const productionTotal = computed(() =>
  productionBreakdown.reduce((sum, item) => sum + item.liters, 0)
)

const alerts = [
  { label: 'Planillas', count: 1, tone: 'high' },
  { label: 'Anticipos', count: 1, tone: 'medium' },
  { label: 'Inventario', count: 1, tone: 'high' },
]
const alertsTotal = computed(() => alerts.reduce((sum, item) => sum + item.count, 0))

const summaryLine = computed(() => {
  const visits = producersAttended
  const pending = alertsTotal.value
  return `${litersToday.toLocaleString('es-NI')} L acopiados hoy · ${visits} productores atendidos · ${routesCompleted}/${routesTotal} rutas completadas · ${pending} alertas por revisar.`
})

const sparklinePoints = computed(() => {
  const values = litersTrend
  const max = Math.max(...values)
  const min = Math.min(...values)
  const range = max - min || 1
  const width = 120
  const height = 36

  return values
    .map((value, index) => {
      const x = (index / (values.length - 1)) * width
      const y = height - ((value - min) / range) * (height - 4) - 2
      return `${x},${y}`
    })
    .join(' ')
})

const sparklineArea = computed(() => {
  const points = sparklinePoints.value.split(' ')
  const last = points[points.length - 1].split(',')
  const first = points[0].split(',')
  return `M ${first[0]},36 L ${points.join(' L ')} L ${last[0]},36 Z`
})

const donutSegments = computed(() => {
  const segments = [
    { value: routesCompleted, color: '#34C759', label: 'Completadas' },
    { value: routesInProgress, color: '#007AFF', label: 'En proceso' },
    { value: routesPending, color: '#E5E5E5', label: 'Pendientes' },
  ]
  const total = segments.reduce((sum, s) => sum + s.value, 0) || 1
  let offset = 0
  const circumference = 2 * Math.PI * 28

  return segments.map((segment) => {
    const pct = segment.value / total
    const dash = pct * circumference
    const current = {
      ...segment,
      dash,
      offset,
      pct: Math.round(pct * 100),
    }
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
  <section class="bg-white rounded-[28px] border border-[#E5E5E5] shadow-sm overflow-hidden mb-6">
    <div class="px-6 pt-6 pb-5 border-b border-[#E5E5E5] bg-gradient-to-br from-[#F5F5F7] via-white to-[#E5F1FF]/30">
      <p class="text-[11px] tracking-[0.22em] uppercase text-[#007AFF] flex items-center gap-2">
        <span class="inline-block w-6 h-0.5 rounded-full bg-[#007AFF]" />
        Mesa de operaciones
      </p>
      <p class="text-sm text-[#8E8E93] mt-2 capitalize">{{ formattedDateTime }}</p>
      <h1 class="text-3xl md:text-4xl font-display font-bold text-[#1D1D1F] mt-1">
        Buenos días, Administrador
      </h1>
      <p class="text-sm text-[#6E6E73] mt-2 max-w-4xl">{{ summaryLine }}</p>
    </div>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <!-- Litros acopiados -->
      <article class="rounded-2xl border border-[#E5E5E5] p-4 bg-[#FAFAFA] hover:border-[#007AFF]/30 transition-colors">
        <div class="flex items-start justify-between gap-3 mb-3">
          <div>
            <p class="text-xs font-medium text-[#8E8E93]">Litros acopiados</p>
            <p class="text-2xl font-display font-bold text-[#1D1D1F] mt-0.5">
              {{ litersToday.toLocaleString('es-NI') }} L
            </p>
            <p class="text-xs text-[#8E8E93] mt-0.5">de {{ litersTarget.toLocaleString('es-NI') }} programados</p>
          </div>
          <div class="p-2.5 rounded-xl bg-gradient-to-br from-[#007AFF] to-[#5AC8FA] shadow-sm">
            <Droplets class="w-5 h-5 text-white" />
          </div>
        </div>
        <div class="flex items-end justify-between gap-3">
          <div class="flex-1">
            <div class="h-1.5 bg-[#E5E5E5] rounded-full overflow-hidden">
              <div class="h-full bg-[#007AFF] rounded-full" :style="{ width: `${litersProgress}%` }" />
            </div>
            <p class="text-xs text-[#34C759] font-semibold mt-1.5">↑ 8.5% vs ayer</p>
          </div>
          <svg viewBox="0 0 120 36" class="w-28 h-9 shrink-0" aria-hidden="true">
            <path :d="sparklineArea" fill="#007AFF" fill-opacity="0.12" />
            <polyline
              :points="sparklinePoints"
              fill="none"
              stroke="#007AFF"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </div>
      </article>

      <!-- Productores atendidos -->
      <article class="rounded-2xl border border-[#E5E5E5] p-4 bg-[#FAFAFA] hover:border-[#34C759]/30 transition-colors">
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1">
            <p class="text-xs font-medium text-[#8E8E93]">Productores atendidos</p>
            <p class="text-2xl font-display font-bold text-[#1D1D1F] mt-0.5">
              {{ producersAttended }}/{{ producersScheduled }}
            </p>
            <p class="text-xs text-[#8E8E93] mt-0.5">{{ producersScheduled - producersAttended }} pendientes hoy</p>
          </div>
          <div class="relative w-16 h-16 shrink-0">
            <svg viewBox="0 0 72 72" class="w-16 h-16 -rotate-90">
              <circle cx="36" cy="36" r="28" fill="none" stroke="#E5E5E5" stroke-width="8" />
              <circle
                cx="36"
                cy="36"
                r="28"
                fill="none"
                stroke="#34C759"
                stroke-width="8"
                stroke-linecap="round"
                :stroke-dasharray="`${producersProgress * 1.76} 176`"
              />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-[#1D1D1F]">
              {{ producersProgress }}%
            </span>
          </div>
        </div>
        <div class="mt-3 flex items-center gap-2">
          <div class="p-2 rounded-xl bg-gradient-to-br from-[#34C759] to-[#30D158]">
            <Users class="w-4 h-4 text-white" />
          </div>
          <p class="text-xs text-[#34C759] font-semibold">↑ 3.2% cobertura semanal</p>
        </div>
      </article>

      <!-- Rutas completadas -->
      <article class="rounded-2xl border border-[#E5E5E5] p-4 bg-[#FAFAFA] hover:border-[#FF9500]/30 transition-colors">
        <div class="flex items-start justify-between gap-3 mb-3">
          <div>
            <p class="text-xs font-medium text-[#8E8E93]">Rutas completadas</p>
            <p class="text-2xl font-display font-bold text-[#1D1D1F] mt-0.5">
              {{ routesCompleted }}/{{ routesTotal }}
            </p>
            <p class="text-xs text-[#8E8E93] mt-0.5">{{ routesInProgress }} en proceso ahora</p>
          </div>
          <div class="p-2.5 rounded-xl bg-gradient-to-br from-[#FF9500] to-[#FF9F0A] shadow-sm">
            <MapPin class="w-5 h-5 text-white" />
          </div>
        </div>
        <div class="flex items-center gap-4">
          <svg viewBox="0 0 72 72" class="w-16 h-16 shrink-0 -rotate-90">
            <circle
              v-for="(segment, index) in donutSegments"
              :key="segment.label"
              cx="36"
              cy="36"
              r="28"
              fill="none"
              :stroke="segment.color"
              stroke-width="10"
              stroke-linecap="butt"
              :stroke-dasharray="`${segment.dash} ${176 - segment.dash}`"
              :stroke-dashoffset="-donutSegments.slice(0, index).reduce((sum, s) => sum + s.dash, 0)"
            />
          </svg>
          <div class="space-y-1 text-xs">
            <p v-for="segment in donutSegments" :key="segment.label" class="flex items-center gap-2 text-[#6E6E73]">
              <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: segment.color }" />
              {{ segment.label }} · {{ segment.value }}
            </p>
          </div>
        </div>
      </article>

      <!-- Pagos de la semana -->
      <article class="rounded-2xl border border-[#E5E5E5] p-4 bg-[#FAFAFA] hover:border-[#AF52DE]/30 transition-colors">
        <div class="flex items-start justify-between gap-3 mb-4">
          <div>
            <p class="text-xs font-medium text-[#8E8E93]">Pagos de la semana</p>
            <p class="text-2xl font-display font-bold text-[#1D1D1F] mt-0.5">
              C$ {{ paymentsWeek.toLocaleString('es-NI') }}
            </p>
            <p class="text-xs text-[#8E8E93] mt-0.5">C$ {{ paymentsPending.toLocaleString('es-NI') }} pendientes</p>
          </div>
          <div class="p-2.5 rounded-xl bg-gradient-to-br from-[#AF52DE] to-[#BF5AF2] shadow-sm">
            <DollarSign class="w-5 h-5 text-white" />
          </div>
        </div>
        <div class="space-y-2">
          <div>
            <div class="flex justify-between text-xs mb-1">
              <span class="text-[#6E6E73]">Liquidado</span>
              <span class="font-semibold text-[#1D1D1F]">{{ paymentsPaidPct }}%</span>
            </div>
            <div class="h-2 bg-[#E5E5E5] rounded-full overflow-hidden">
              <div class="h-full bg-[#AF52DE] rounded-full" :style="{ width: `${paymentsPaidPct}%` }" />
            </div>
          </div>
          <div class="flex items-end gap-1 h-12 pt-1">
            <div class="flex-1 bg-[#AF52DE] rounded-t-md h-full" title="Pagado" />
            <div
              class="w-8 bg-[#E5E5E5] rounded-t-md"
              :style="{ height: `${Math.round((paymentsPending / paymentsWeek) * 100)}%` }"
              title="Pendiente"
            />
          </div>
        </div>
      </article>

      <!-- Producción diaria -->
      <article class="rounded-2xl border border-[#E5E5E5] p-4 bg-[#FAFAFA] hover:border-[#34C759]/30 transition-colors">
        <div class="flex items-start justify-between gap-3 mb-3">
          <div>
            <p class="text-xs font-medium text-[#8E8E93]">Producción diaria</p>
            <p class="text-2xl font-display font-bold text-[#1D1D1F] mt-0.5">
              {{ productionTotal.toLocaleString('es-NI') }} L
            </p>
            <p class="text-xs text-[#8E8E93] mt-0.5">queso, crema y suero</p>
          </div>
          <div class="p-2.5 rounded-xl bg-gradient-to-br from-[#34C759] to-[#30D158] shadow-sm">
            <Package class="w-5 h-5 text-white" />
          </div>
        </div>
        <div class="space-y-2">
          <div
            v-for="item in productionBreakdown"
            :key="item.label"
            class="flex items-center gap-2"
          >
            <span class="text-xs text-[#6E6E73] w-12 shrink-0">{{ item.label }}</span>
            <div class="flex-1 h-2 bg-[#E5E5E5] rounded-full overflow-hidden">
              <div
                class="h-full rounded-full"
                :style="{
                  width: `${Math.round((item.liters / productionTotal) * 100)}%`,
                  backgroundColor: item.color,
                }"
              />
            </div>
            <span class="text-xs font-medium text-[#1D1D1F] w-14 text-right">
              {{ item.liters.toLocaleString('es-NI') }} L
            </span>
          </div>
        </div>
      </article>

      <!-- Alertas operativas -->
      <article class="rounded-2xl border border-[#E5E5E5] p-4 bg-[#FAFAFA] hover:border-[#FF3B30]/30 transition-colors">
        <div class="flex items-start justify-between gap-3 mb-3">
          <div>
            <p class="text-xs font-medium text-[#8E8E93]">Alertas operativas</p>
            <p class="text-2xl font-display font-bold text-[#1D1D1F] mt-0.5">{{ alertsTotal }}</p>
            <p class="text-xs text-[#8E8E93] mt-0.5">requieren atención hoy</p>
          </div>
          <div class="p-2.5 rounded-xl bg-gradient-to-br from-[#FF3B30] to-[#FF453A] shadow-sm">
            <AlertTriangle class="w-5 h-5 text-white" />
          </div>
        </div>
        <div class="flex items-end gap-2 h-14 mb-3">
          <div
            v-for="alert in alerts"
            :key="alert.label"
            class="flex-1 flex flex-col items-center justify-end gap-1"
          >
            <div
              class="w-full rounded-t-lg transition-all"
              :class="alert.tone === 'high' ? 'bg-[#FF3B30]' : alert.tone === 'medium' ? 'bg-[#FF9500]' : 'bg-[#007AFF]'"
              :style="{ height: `${alert.count * 28}px` }"
            />
            <span class="text-[10px] text-[#8E8E93]">{{ alert.label }}</span>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="alert in alerts"
            :key="`${alert.label}-badge`"
            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
            :class="alertToneClass[alert.tone]"
          >
            {{ alert.count }} {{ alert.label.toLowerCase() }}
          </span>
        </div>
      </article>
    </div>
  </section>
</template>
