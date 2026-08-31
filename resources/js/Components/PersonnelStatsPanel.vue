<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import {
  BriefcaseBusiness,
  ClipboardPlus,
  Pencil,
  Plus,
  Power,
  PowerOff,
  Shield,
  UserCheck,
  UserRound,
  UsersRound,
  UserX,
} from '@lucide/vue'

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
  roles: {
    type: Array,
    default: () => [],
  },
  pendingRoleId: {
    type: [Number, String, null],
    default: null,
  },
})

const emit = defineEmits(['agregar-colaborador', 'nuevo-rol', 'filtrar-estado', 'editar-rol', 'toggle-rol'])

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
const suspended = computed(() => props.stats.suspended || 0)
const retired = computed(() => props.stats.retired || 0)
const rolesCount = computed(() => props.stats.roles || 0)
const inssInsured = computed(() => props.stats.inss_insured || 0)
const nuevas = computed(() => props.stats.new_this_month || 0)

const percent = (value) => {
  if (!total.value) return 0
  return Math.round((value / total.value) * 100)
}

const summaryLine = computed(() => {
  const parts = [
    `${total.value} colaboradores registrados`,
    `${active.value} activos`,
    `${rolesCount.value} cargos disponibles`,
  ]

  if (suspended.value > 0) {
    parts.push(`${suspended.value} suspendidos`)
  }

  if (nuevas.value > 0) {
    parts.push(`${nuevas.value} altas este mes`)
  }

  return parts.join(' · ')
})

const statusBars = computed(() => {
  const items = [
    { label: 'Activos', count: active.value, tone: 'low' },
    { label: 'Suspendidos', count: suspended.value, tone: 'medium' },
    { label: 'Retirados', count: retired.value, tone: 'high' },
  ]

  return items.map((item) => ({
    ...item,
    widthPct: total.value && item.count
      ? Math.max(4, Math.round((item.count / total.value) * 100))
      : 0,
    barClass: item.tone === 'high'
      ? 'bg-[#8E8E93]'
      : item.tone === 'medium'
        ? 'bg-[#FF9500]'
        : 'bg-[#34C759]',
  }))
})

const statusLegend = computed(() => [
  { label: 'Activos', value: active.value, pct: percent(active.value), dot: 'bg-[#34C759]', valueClass: 'text-[#1D1D1F]', filter: 'activo' },
  { label: 'Suspendidos', value: suspended.value, pct: percent(suspended.value), dot: 'bg-[#FF9500]', valueClass: 'text-[#FF9500]', filter: 'suspendido' },
  { label: 'Retirados', value: retired.value, pct: percent(retired.value), dot: 'bg-[#8E8E93]', valueClass: 'text-[#6E6E73]', filter: 'retirado' },
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

const roleBars = computed(() => {
  const items = (props.stats.by_role || []).slice(0, 5)
  const max = Math.max(...items.map((item) => item.count), 1)

  return items.map((item) => ({
    ...item,
    widthPct: item.count ? Math.max(8, Math.round((item.count / max) * 100)) : 0,
  }))
})

const metrics = computed(() => [
  {
    key: 'total',
    label: 'Colaboradores',
    value: total.value,
    icon: UsersRound,
    tone: 'text-[#007AFF] bg-[#E5F1FF]',
  },
  {
    key: 'active',
    label: 'Activos',
    value: active.value,
    icon: UserCheck,
    tone: 'text-[#1D7A32] bg-[#E8F8E8]',
  },
  {
    key: 'suspended',
    label: 'Suspendidos',
    value: suspended.value,
    icon: UserX,
    tone: 'text-[#FF9500] bg-[#FFF4E5]',
  },
  {
    key: 'inss',
    label: 'Con INSS',
    value: inssInsured.value,
    icon: Shield,
    tone: 'text-[#AF52DE] bg-[#F8F2FC]',
  },
])
</script>

<template>
  <section class="relative mb-6 overflow-hidden rounded-[28px] border border-[#E5E5E5] bg-white shadow-sm">
    <div
      class="pointer-events-none absolute inset-0 opacity-60"
      style="background-image: linear-gradient(rgba(0,122,255,0.035) 1px, transparent 1px), linear-gradient(90deg, rgba(0,122,255,0.035) 1px, transparent 1px); background-size: 28px 28px;"
    />

    <div class="relative space-y-5 p-5 sm:p-6 lg:p-7">
      <div class="rounded-2xl border border-[#E5E5E5] bg-gradient-to-br from-[#F5F5F7] via-white to-[#E5F1FF]/30 px-5 py-5">
        <p class="flex items-center gap-2 text-[11px] uppercase tracking-[0.22em] text-[#007AFF]">
          <span class="inline-block h-0.5 w-6 rounded-full bg-[#007AFF]" />
          Gestión de talento
        </p>
        <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="min-w-0">
            <h2 class="font-display text-3xl font-bold tracking-tight text-[#1D1D1F] sm:text-4xl">
              Personal
            </h2>
            <p class="mt-1 text-sm capitalize text-[#8E8E93]">{{ dateLabel }}</p>
            <p class="mt-2 max-w-4xl text-sm text-[#6E6E73]">{{ summaryLine }}</p>
          </div>
          <div class="flex flex-col gap-2 self-start sm:flex-row">
            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#AF52DE]/30 bg-[#F8F2FC] px-4 py-2.5 text-sm font-semibold text-[#AF52DE] transition-colors hover:bg-[#F0E4FA]"
              @click="emit('nuevo-rol')"
            >
              <ClipboardPlus class="size-4" />
              Nuevo rol
            </button>
            <button
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#007AFF] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#0056CC]"
              @click="emit('agregar-colaborador')"
            >
              <Plus class="size-4" />
              Colaborador
            </button>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div
          v-for="metric in metrics"
          :key="metric.key"
          class="flex items-center gap-3 rounded-2xl border border-[#E5E5E5] bg-white/80 px-3.5 py-3"
        >
          <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="metric.tone">
            <component :is="metric.icon" class="size-4" />
          </span>
          <div class="min-w-0">
            <p class="truncate text-[11px] text-[#8E8E93]">{{ metric.label }}</p>
            <p class="font-display text-lg font-bold tabular-nums leading-tight text-[#1D1D1F]">
              {{ metric.value }}
            </p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA]/80 p-4 sm:p-5">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div>
              <p class="text-[11px] uppercase tracking-[0.18em] text-[#8E8E93]">Estado</p>
              <p class="mt-0.5 text-sm font-semibold text-[#1D1D1F]">Distribución del personal</p>
            </div>
            <button
              type="button"
              class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-[#FFF4E5] px-3 py-2 text-xs font-semibold text-[#FF9500] transition-colors hover:bg-[#FFE8CC]"
              @click="emit('filtrar-estado', 'suspendido')"
            >
              <UserX class="size-3.5" />
              Suspendidos
              <span class="tabular-nums">({{ suspended }})</span>
            </button>
          </div>

          <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-5">
            <div class="min-w-0 flex-1 rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA] px-4 py-3">
              <div class="mb-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-[10px] font-medium uppercase tracking-wide text-[#8E8E93]">Total plantilla</p>
                  <p class="font-display text-lg font-bold tabular-nums leading-tight text-[#1D1D1F]">
                    {{ total }} colaboradores
                  </p>
                </div>
                <div class="shrink-0 rounded-xl bg-gradient-to-br from-[#007AFF] to-[#0056CC] p-2 shadow-sm">
                  <UsersRound class="size-4 text-white" />
                </div>
              </div>

              <div class="space-y-2.5">
                <div
                  v-for="bar in statusBars"
                  :key="bar.label"
                  class="flex min-w-0 items-center gap-2.5"
                >
                  <span class="w-[4.5rem] shrink-0 truncate text-[10px] text-[#8E8E93]">{{ bar.label }}</span>
                  <div class="h-2.5 min-w-0 flex-1 overflow-hidden rounded-full bg-[#E5E5E5]">
                    <div
                      class="h-full rounded-full transition-all"
                      :class="bar.barClass"
                      :style="{ width: `${bar.widthPct}%` }"
                    />
                  </div>
                  <span class="w-6 shrink-0 text-right text-[11px] font-semibold tabular-nums text-[#1D1D1F]">
                    {{ bar.count }}
                  </span>
                </div>
              </div>
            </div>

            <div class="grid shrink-0 grid-cols-3 gap-2 lg:w-[9.5rem] lg:grid-cols-1 lg:gap-2">
              <button
                v-for="item in statusLegend"
                :key="item.label"
                type="button"
                class="rounded-xl border bg-white px-2.5 py-2 text-center transition-colors hover:border-[#007AFF]/30 lg:text-right"
                :class="item.label === 'Suspendidos' ? 'border-[#FFE8CC]' : 'border-[#E5E5E5]'"
                @click="emit('filtrar-estado', item.filter)"
              >
                <div class="mb-0.5 flex items-center justify-center gap-1 lg:justify-end">
                  <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="item.dot" />
                  <span class="truncate text-[10px] text-[#8E8E93]">{{ item.label }}</span>
                </div>
                <p class="font-display text-sm font-bold tabular-nums leading-none" :class="item.valueClass">
                  {{ item.value }}
                </p>
                <p class="mt-0.5 text-[10px] tabular-nums text-[#8E8E93]">{{ item.pct }}%</p>
              </button>
            </div>
          </div>
        </div>

        <div class="flex flex-col rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA]/80 p-4 sm:p-5">
          <div class="mb-1 flex items-start justify-between gap-3">
            <div>
              <p class="text-[11px] uppercase tracking-[0.18em] text-[#8E8E93]">Altas</p>
              <p class="mt-0.5 text-sm font-semibold text-[#1D1D1F]">Colaboradores registrados</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-[#E8F8E8] px-2.5 py-1 text-xs font-semibold tabular-nums text-[#1D7A32]">
              {{ altasDelta }} este mes
            </span>
          </div>
          <p class="mb-4 text-xs text-[#8E8E93]">
            Acumulado en 12 meses:
            <span class="font-semibold tabular-nums text-[#1D1D1F]">{{ acumulado }}</span>
          </p>
          <div class="flex flex-1 flex-col justify-end">
            <svg :viewBox="`0 0 ${chart.width} ${chart.height}`" class="h-24 w-full">
              <defs>
                <linearGradient id="personnelAltasFill" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#007AFF" stop-opacity="0.28" />
                  <stop offset="100%" stop-color="#007AFF" stop-opacity="0" />
                </linearGradient>
              </defs>
              <line v-for="y in [18, 48, 78]" :key="y" x1="0" :y1="y" :x2="chart.width" :y2="y" stroke="#EFEFF4" />
              <polygon :points="chart.area" fill="url(#personnelAltasFill)" />
              <polyline
                :points="chart.line"
                fill="none"
                stroke="#007AFF"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
            <div class="mt-2 flex justify-between text-[10px] uppercase tracking-wider text-[#8E8E93]">
              <span v-for="label in chart.labels" :key="label">{{ label }}</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="roleBars.length" class="rounded-2xl border border-[#E5E5E5] bg-[#FAFAFA]/80 p-4 sm:p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
          <div>
            <p class="text-[11px] uppercase tracking-[0.18em] text-[#8E8E93]">Cargos</p>
            <p class="mt-0.5 text-sm font-semibold text-[#1D1D1F]">Colaboradores por rol</p>
          </div>
          <div class="rounded-xl bg-gradient-to-br from-[#AF52DE] to-[#BF5AF2] p-2 shadow-sm">
            <BriefcaseBusiness class="size-4 text-white" />
          </div>
        </div>
        <div class="space-y-2.5">
          <div
            v-for="role in roleBars"
            :key="role.id"
            class="flex min-w-0 items-center gap-2.5"
          >
            <span class="w-24 shrink-0 truncate text-[10px] text-[#6E6E73]">{{ role.name }}</span>
            <div class="h-2.5 min-w-0 flex-1 overflow-hidden rounded-full bg-[#E5E5E5]">
              <div
                class="h-full rounded-full bg-[#007AFF] transition-all"
                :style="{ width: `${role.widthPct}%` }"
              />
            </div>
            <span class="w-6 shrink-0 text-right text-[11px] font-semibold tabular-nums text-[#1D1D1F]">
              {{ role.count }}
            </span>
          </div>
        </div>
        <div v-if="roles.length" class="mt-4 flex flex-wrap gap-2 border-t border-[#E5E5E5] pt-4">
          <div
            v-for="role in roles"
            :key="role.id"
            class="inline-flex items-center gap-1.5 rounded-full border px-2 py-1 text-[11px] font-semibold"
            :class="role.active ? 'border-[#D9E8FF] bg-[#F1F6FF] text-[#245DA8]' : 'border-[#E5E5E5] bg-[#F5F5F7] text-[#8E8E93]'"
          >
            <BriefcaseBusiness class="size-3" />
            <span>{{ role.name }}</span>
            <span class="rounded-md bg-white/80 px-1 tabular-nums text-[10px] text-[#6E6E73]">{{ role.employees_count }}</span>
            <span class="ml-0.5 flex gap-0.5 border-l border-current/15 pl-1">
              <button type="button" class="rounded p-0.5 hover:bg-white/80" @click="emit('editar-rol', role)"><Pencil class="size-3" /></button>
              <button type="button" :disabled="pendingRoleId === role.id" class="rounded p-0.5 hover:bg-white/80 disabled:opacity-40" @click="emit('toggle-rol', role)"><PowerOff v-if="role.active" class="size-3" /><Power v-else class="size-3" /></button>
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
