<script setup>
import { computed, ref } from 'vue'
import AppShell from '../../../Components/AppShell.vue'
import { router } from '@inertiajs/vue3'
import { Search, Printer } from '@lucide/vue'
import ProducerModal from '../../../Components/ProducerModal.vue'
import ProducerStatsPanel from '../../../Components/ProducerStatsPanel.vue'

const props = defineProps({
  routes: Array,
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      active: 0,
      inactive: 0,
      without_route: 0,
      new_this_month: 0,
      monthly: [],
    }),
  },
  filters: {
    type: Object,
    default: () => ({ route_id: null, week: '' }),
  },
  weeks: {
    type: Array,
    default: () => [],
  },
  report: {
    type: Object,
    default: () => ({
      week: {},
      days: [],
      price: 0,
      rows: [],
      totals: { producers: 0, days: 0, daily: {}, liters: 0, amount: 0 },
    }),
  },
})

const searchQuery = ref('')
const showCreateModal = ref(false)
const routeFilter = ref(props.filters.route_id ? String(props.filters.route_id) : '')
const weekFilter = ref(props.filters.week || '')

const money = (value) =>
  `C$ ${Number(value || 0).toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const liters = (value) =>
  `${Number(value || 0).toLocaleString('es-NI', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} L`

const dayLiters = (value) => {
  if (!value) return '—'
  return Number(value).toLocaleString('es-NI', { minimumFractionDigits: 1, maximumFractionDigits: 1 })
}

const filteredRows = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const rows = props.report.rows || []
  if (!query) return rows

  return rows.filter((row) =>
    [row.full_name, row.identity_number, row.phone, row.code, row.route?.name, row.route?.code]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(query))
  )
})

const applyFilters = () => {
  router.get('/producers', {
    route_id: routeFilter.value || undefined,
    week: weekFilter.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const openCreateModal = () => {
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
}

const handleCreate = (form) => {
  form.post('/producers', {
    onSuccess: () => {
      closeCreateModal()
    },
  })
}

const printReport = () => {
  window.print()
}
</script>

<template>
  <AppShell>
    <ProducerStatsPanel
      :stats="stats"
      @registrar-cliente="openCreateModal"
    />

    <div class="rounded-[28px] border border-[#E5E5E5] bg-white overflow-hidden shadow-sm print:shadow-none print:border-0">
      <div class="p-4 border-b border-[#E5E5E5] flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between print:hidden">
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 flex-1">
          <select
            v-model="routeFilter"
            @change="applyFilters"
            class="bg-[#F5F5F7] border-none rounded-full focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2 px-4 text-sm text-[#1D1D1F]"
          >
            <option value="">Todas las rutas</option>
            <option v-for="route in routes" :key="route.id" :value="String(route.id)">
              {{ route.code }} — {{ route.name }}
            </option>
          </select>
          <select
            v-model="weekFilter"
            @change="applyFilters"
            class="bg-[#F5F5F7] border-none rounded-full focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2 px-4 text-sm text-[#1D1D1F]"
          >
            <option v-for="week in weeks" :key="week.value" :value="week.value">
              Semana {{ week.label }}
            </option>
          </select>
          <div class="relative flex-1 sm:max-w-xs">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Buscar productor..."
              class="pl-10 pr-4 py-2 bg-[#F5F5F7] border-none rounded-full focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-sm w-full text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
        </div>
        <button
          type="button"
          @click="printReport"
          class="inline-flex items-center px-4 py-2 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] text-sm font-medium"
        >
          <Printer class="w-4 h-4 mr-2" />
          Imprimir reporte
        </button>
      </div>

      <div class="px-4 py-3 border-b border-[#E5E5E5] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <p class="text-sm font-semibold text-[#1D1D1F]">Reporte de pago semanal</p>
          <p class="text-xs text-[#8E8E93]">
            Acopio del {{ report.week.start }} al {{ report.week.end }} · pago el jueves {{ report.week.pay_day }}
          </p>
        </div>
        <p class="text-xs text-[#8E8E93]">
          Precio: {{ money(report.price) }} / L
        </p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-[#F5F5F7] text-left text-xs uppercase tracking-wide text-[#8E8E93]">
            <tr>
              <th class="px-4 py-3 font-semibold sticky left-0 bg-[#F5F5F7]">Productor</th>
              <th class="px-3 py-3 font-semibold">Ruta</th>
              <th
                v-for="day in report.days"
                :key="day.date"
                class="px-2 py-3 font-semibold text-center min-w-[3.5rem]"
              >
                <span class="block">{{ day.label }}</span>
                <span class="block normal-case font-normal text-[10px]">{{ day.day }}</span>
              </th>
              <th class="px-3 py-3 font-semibold text-right">Total L</th>
              <th class="px-4 py-3 font-semibold text-right">Pago jueves</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#E5E5E5]">
            <tr v-for="row in filteredRows" :key="row.id" class="hover:bg-[#F5F5F7]">
              <td class="px-4 py-3 sticky left-0 bg-white">
                <p class="font-medium text-[#1D1D1F]">{{ row.full_name }}</p>
                <p class="text-xs text-[#8E8E93]">{{ row.identity_number || row.phone || '—' }}</p>
              </td>
              <td class="px-3 py-3 text-[#6E6E73] whitespace-nowrap">
                {{ row.route ? row.route.code : '—' }}
              </td>
              <td
                v-for="day in report.days"
                :key="`${row.id}-${day.date}`"
                class="px-2 py-3 text-center tabular-nums"
                :class="row.daily?.[day.date] ? 'text-[#1D1D1F]' : 'text-[#C7C7CC]'"
              >
                {{ dayLiters(row.daily?.[day.date]) }}
              </td>
              <td class="px-3 py-3 text-right tabular-nums font-medium text-[#1D1D1F]">{{ liters(row.liters) }}</td>
              <td class="px-4 py-3 text-right tabular-nums font-semibold text-[#1D1D1F]">{{ money(row.amount) }}</td>
            </tr>
            <tr v-if="!filteredRows.length">
              <td :colspan="4 + (report.days?.length || 0)" class="px-4 py-10 text-center text-[#8E8E93]">
                No hay productores en esta ruta para la semana seleccionada.
              </td>
            </tr>
          </tbody>
          <tfoot v-if="filteredRows.length" class="bg-[#F5F5F7] font-semibold text-[#1D1D1F]">
            <tr>
              <td class="px-4 py-3" colspan="2">
                Total ({{ report.totals.producers }} productores)
              </td>
              <td
                v-for="day in report.days"
                :key="`total-${day.date}`"
                class="px-2 py-3 text-center tabular-nums"
              >
                {{ dayLiters(report.totals.daily?.[day.date]) }}
              </td>
              <td class="px-3 py-3 text-right tabular-nums">{{ liters(report.totals.liters) }}</td>
              <td class="px-4 py-3 text-right tabular-nums">{{ money(report.totals.amount) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <ProducerModal
      :show="showCreateModal"
      :routes="routes"
      :default-route-id="routeFilter"
      @close="closeCreateModal"
      @submit="handleCreate"
    />
  </AppShell>
</template>
