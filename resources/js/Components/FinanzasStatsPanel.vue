<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import {
  Plus,
  Wallet,
  ArrowDownCircle,
  ArrowUpCircle,
  CreditCard,
  TrendingUp,
} from '@lucide/vue'

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['registrar-movimiento'])

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

const formatMoney = (value) =>
  `C$ ${Number(value || 0).toLocaleString('es-NI', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`

const total = computed(() => props.stats.total || 0)
const gastosMes = computed(() => props.stats.gastos_mes || 0)
const pagosMes = computed(() => props.stats.pagos_mes || 0)
const ingresosMes = computed(() => props.stats.ingresos_mes || 0)
const balanceMes = computed(() => props.stats.balance_mes || 0)
const movimientosMes = computed(() => props.stats.movimientos_mes || 0)

const monthly = computed(() => props.stats.monthly || [])

const chart = computed(() => {
  const values = monthly.value.map((month) => Math.max(month.ingresos, month.salidas, 0))
  const width = 260
  const height = 96
  const max = Math.max(...values, 1)
  const step = monthly.value.length > 1 ? width / (monthly.value.length - 1) : width

  const buildLine = (key) =>
    monthly.value
      .map((month, index) => {
        const x = index * step
        const y = height - (month[key] / max) * (height - 8) - 4
        return `${x},${y}`
      })
      .join(' ')

  const labels = [
    monthly.value[0]?.label,
    monthly.value[Math.floor(monthly.value.length / 2)]?.label,
    monthly.value[monthly.value.length - 1]?.label,
  ]
    .filter(Boolean)
    .map((label) => String(label).toUpperCase())

  return {
    width,
    height,
    ingresosLine: buildLine('ingresos'),
    salidasLine: buildLine('salidas'),
    labels,
  }
})

const metrics = computed(() => [
  {
    key: 'ingresos',
    label: 'Ingresos mes',
    value: formatMoney(ingresosMes.value),
    icon: ArrowUpCircle,
    tone: 'text-[#1D7A32] bg-[#E8F8E8]',
  },
  {
    key: 'gastos',
    label: 'Gastos mes',
    value: formatMoney(gastosMes.value),
    icon: ArrowDownCircle,
    tone: 'text-[#FF9500] bg-[#FFF4E5]',
  },
  {
    key: 'pagos',
    label: 'Pagos mes',
    value: formatMoney(pagosMes.value),
    icon: CreditCard,
    tone: 'text-[#FF3B30] bg-[#FFE5E5]',
  },
  {
    key: 'balance',
    label: 'Balance mes',
    value: formatMoney(balanceMes.value),
    icon: TrendingUp,
    tone: balanceMes.value >= 0 ? 'text-[#007AFF] bg-[#E5F1FF]' : 'text-[#FF3B30] bg-[#FFE5E5]',
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
            Finanzas
          </h2>
          <p class="mt-1 text-sm text-[#8E8E93] capitalize">{{ dateLabel }}</p>
          <p class="mt-1 text-xs text-[#8E8E93]">
            {{ movimientosMes }} movimientos este mes · {{ total }} registrados
          </p>
        </div>
        <button
          type="button"
          @click="emit('registrar-movimiento')"
          class="inline-flex items-center justify-center gap-2 self-start rounded-xl bg-[#007AFF] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0056CC] transition-colors"
        >
          <Plus class="w-4 h-4" />
          Registrar movimiento
        </button>
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
            <p class="text-sm sm:text-base font-display font-bold text-[#1D1D1F] tabular-nums leading-tight">
              {{ metric.value }}
            </p>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA]/80 p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3 mb-4">
          <div>
            <p class="text-[11px] tracking-[0.18em] uppercase text-[#8E8E93]">Flujo</p>
            <p class="text-sm font-semibold text-[#1D1D1F] mt-0.5">Ingresos vs salidas (12 meses)</p>
          </div>
          <div class="p-2 rounded-xl bg-gradient-to-br from-[#007AFF] to-[#0056CC] shadow-sm shrink-0">
            <Wallet class="w-4 h-4 text-white" />
          </div>
        </div>
        <svg :viewBox="`0 0 ${chart.width} ${chart.height}`" class="w-full h-24">
          <line v-for="y in [18, 48, 78]" :key="y" x1="0" :y1="y" :x2="chart.width" :y2="y" stroke="#EFEFF4" />
          <polyline
            :points="chart.ingresosLine"
            fill="none"
            stroke="#34C759"
            stroke-width="2.5"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
          <polyline
            :points="chart.salidasLine"
            fill="none"
            stroke="#FF3B30"
            stroke-width="2.5"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        <div class="mt-2 flex justify-between text-[10px] tracking-wider text-[#8E8E93] uppercase">
          <span v-for="label in chart.labels" :key="label">{{ label }}</span>
        </div>
        <div class="mt-3 flex flex-wrap gap-4 text-xs text-[#6E6E73]">
          <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#34C759]"></span> Ingresos</span>
          <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#FF3B30]"></span> Gastos + pagos</span>
        </div>
      </div>
    </div>
  </section>
</template>
