<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Activity, AlertCircle, Clock, Droplets, FileText, FlaskConical, Package, Thermometer } from '@lucide/vue'
import AppShell from '../../Components/AppShell.vue'
import MorningOperationsPanel from '../../Components/MorningOperationsPanel.vue'
import RouteMap from '../../Components/RouteMap.vue'
import StatusBadge from '../../Components/StatusBadge.vue'

const props = defineProps({
  userName: { type: String, default: 'Usuario' },
  overview: {
    type: Object,
    default: () => ({
      liters_today: 0,
      liters_yesterday: 0,
      liters_trend_percent: null,
      producers_attended: 0,
      producers_scheduled: 0,
      routes_completed: 0,
      routes_in_progress: 0,
      routes_pending: 0,
      routes_total: 0,
      finance: { income: 0, outflow: 0, movements: 0 },
      inventory: { active: 0, low_stock: 0, zero_stock: 0 },
      alerts: { total: 0, items: [] },
      liters_trend: [],
    }),
  },
  routesStatus: { type: Array, default: () => [] },
  weeklyData: { type: Array, default: () => [] },
  qualityMetrics: { type: Array, default: () => [] },
  pendingOperations: { type: Array, default: () => [] },
  recentActivity: { type: Array, default: () => [] },
})

const numberFormatter = new Intl.NumberFormat('es-NI', { maximumFractionDigits: 2 })
const chartMaximum = computed(() => Math.max(1, ...props.weeklyData.map((item) => item.liters)))
const weekTotal = computed(() => props.weeklyData.reduce((sum, item) => sum + item.liters, 0))

const qualityIcon = {
  temperature: Thermometer,
  acidity: FlaskConical,
  fat: Droplets,
  coverage: FileText,
}

const statusLabel = {
  completed: 'Completada',
  in_progress: 'En proceso',
  pending: 'Pendiente',
}

const relativeTime = (value) => {
  if (!value) return ''
  const seconds = Math.round((new Date(value).getTime() - Date.now()) / 1000)
  const formatter = new Intl.RelativeTimeFormat('es', { numeric: 'auto' })

  if (Math.abs(seconds) < 60) return formatter.format(seconds, 'second')
  const minutes = Math.round(seconds / 60)
  if (Math.abs(minutes) < 60) return formatter.format(minutes, 'minute')
  const hours = Math.round(minutes / 60)
  if (Math.abs(hours) < 24) return formatter.format(hours, 'hour')
  return formatter.format(Math.round(hours / 24), 'day')
}
</script>

<template>
  <AppShell>
    <Head title="Dashboard" />

    <MorningOperationsPanel :overview="overview" :user-name="userName" />

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <RouteMap :routes="routesStatus" />
      </div>

      <section class="rounded-2xl border border-[#E5E5E5] bg-white p-5 shadow-sm">
        <h2 class="mb-4 font-display text-base font-semibold text-[#1D1D1F]">Estado de rutas</h2>
        <div v-if="routesStatus.length" class="flex flex-col gap-3">
          <div v-for="route in routesStatus" :key="route.id" class="rounded-xl bg-[#F5F5F7] p-3">
            <div class="mb-2 flex items-center justify-between gap-2">
              <p class="truncate text-sm font-medium text-[#1D1D1F]">{{ route.name }}</p>
              <StatusBadge :status="route.status" :label="statusLabel[route.status]" />
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-[#8E8E93]">{{ route.attended }}/{{ route.producers }} productores</span>
              <span class="text-[#6E6E73]">{{ numberFormatter.format(route.liters) }} L</span>
            </div>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[#E5E5E5]">
              <div
                class="h-full rounded-full transition-all duration-300"
                :class="route.status === 'completed' ? 'bg-[#34C759]' : route.status === 'in_progress' ? 'bg-[#007AFF]' : 'bg-[#8E8E93]'"
                :style="{ width: `${route.progress}%` }"
              />
            </div>
          </div>
        </div>
        <div v-else class="flex min-h-48 flex-col items-center justify-center gap-2 text-center">
          <Activity class="size-8 text-[#8E8E93]" />
          <p class="text-sm font-medium text-[#1D1D1F]">Sin rutas activas</p>
        </div>
      </section>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <section class="rounded-2xl border border-[#E5E5E5] bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-start justify-between gap-3">
          <div>
            <h2 class="font-display text-base font-semibold text-[#1D1D1F]">Acopio semanal</h2>
            <p class="mt-1 text-xs text-[#8E8E93]">Litros registrados por día</p>
          </div>
          <p class="text-sm font-semibold text-[#007AFF]">{{ numberFormatter.format(weekTotal) }} L</p>
        </div>

        <div class="flex h-56 items-end justify-between gap-2">
          <div v-for="data in weeklyData" :key="data.date" class="flex h-full flex-1 flex-col items-center justify-end">
            <span class="mb-1 text-[10px] font-medium text-[#6E6E73]">{{ numberFormatter.format(data.liters) }}</span>
            <div class="flex h-44 w-full items-end">
              <div
                class="w-full rounded-t-lg bg-[#007AFF] transition-colors duration-300 hover:bg-[#0056CC]"
                :class="data.liters > 0 ? 'min-h-1' : ''"
                :style="{ height: `${(data.liters / chartMaximum) * 100}%` }"
              />
            </div>
            <span class="mt-2 text-xs text-[#8E8E93]">{{ data.day }}</span>
          </div>
        </div>
      </section>

      <section class="rounded-2xl border border-[#E5E5E5] bg-white p-5 shadow-sm">
        <div class="mb-4">
          <h2 class="font-display text-base font-semibold text-[#1D1D1F]">Calidad de leche</h2>
          <p class="mt-1 text-xs text-[#8E8E93]">Promedios de la semana actual</p>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div v-for="metric in qualityMetrics" :key="metric.key" class="rounded-xl bg-[#F5F5F7] p-3">
            <div class="mb-2 flex items-center justify-between">
              <component :is="qualityIcon[metric.key]" class="size-4 text-[#8E8E93]" />
              <span class="size-1.5 rounded-full" :class="metric.value === null ? 'bg-[#8E8E93]' : 'bg-[#34C759]'" />
            </div>
            <p class="mb-1 text-xs text-[#8E8E93]">{{ metric.metric }}</p>
            <p class="font-display text-lg font-bold text-[#1D1D1F]">
              {{ metric.value === null ? 'Sin datos' : `${numberFormatter.format(metric.value)}${metric.unit}` }}
            </p>
          </div>
        </div>
      </section>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <section class="rounded-2xl border border-[#E5E5E5] bg-white p-5 shadow-sm">
        <h2 class="mb-4 font-display text-base font-semibold text-[#1D1D1F]">Operaciones pendientes</h2>
        <div v-if="pendingOperations.length" class="flex flex-col gap-3">
          <div v-for="operation in pendingOperations" :key="`${operation.type}-${operation.description}`" class="flex items-start gap-3 rounded-xl border border-[#E5E5E5] p-3 transition-colors hover:border-[#007AFF]">
            <div class="rounded-xl p-2" :class="operation.priority === 'high' ? 'bg-[#FFE5E5] text-[#FF3B30]' : 'bg-[#FFF4E5] text-[#FF9500]'">
              <AlertCircle v-if="operation.priority === 'high'" class="size-4" />
              <Clock v-else class="size-4" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-[#1D1D1F]">{{ operation.type }}</p>
              <p class="text-sm text-[#8E8E93]">{{ operation.description }}</p>
              <p class="mt-1.5 text-xs text-[#8E8E93]">{{ operation.area }}</p>
            </div>
            <Link :href="operation.href" class="shrink-0 text-xs font-semibold text-[#007AFF] hover:text-[#0056CC]">{{ operation.action }}</Link>
          </div>
        </div>
        <div v-else class="flex min-h-40 flex-col items-center justify-center gap-2 text-center">
          <Package class="size-8 text-[#34C759]" />
          <p class="text-sm font-medium text-[#1D1D1F]">No hay operaciones pendientes</p>
        </div>
      </section>

      <section class="rounded-2xl border border-[#E5E5E5] bg-white p-5 shadow-sm">
        <h2 class="mb-4 font-display text-base font-semibold text-[#1D1D1F]">Actividad reciente</h2>
        <div v-if="recentActivity.length" class="flex flex-col gap-2">
          <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start gap-3 rounded-xl p-2.5 transition-colors hover:bg-[#F5F5F7]">
            <div class="shrink-0 rounded-xl bg-[#E8F8E8] p-2 text-[#34C759]"><Activity class="size-4" /></div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-[#1D1D1F]">{{ activity.event }}</p>
              <p class="truncate text-sm text-[#8E8E93]">{{ activity.details }}</p>
              <p class="mt-0.5 text-xs text-[#8E8E93]"><span v-if="activity.user">{{ activity.user }} · </span>{{ relativeTime(activity.occurred_at) }}</p>
            </div>
          </div>
        </div>
        <div v-else class="flex min-h-40 flex-col items-center justify-center gap-2 text-center">
          <Activity class="size-8 text-[#8E8E93]" />
          <p class="text-sm font-medium text-[#1D1D1F]">Todavía no hay actividad registrada</p>
        </div>
      </section>
    </div>
  </AppShell>
</template>
