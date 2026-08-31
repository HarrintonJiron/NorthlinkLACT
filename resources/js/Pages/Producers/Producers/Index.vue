<script setup>
import { computed, ref } from 'vue'
import AppShell from '../../../Components/AppShell.vue'
import { router } from '@inertiajs/vue3'
import { Search, Printer, Pencil, Eye } from '@lucide/vue'
import ProducerModal from '../../../Components/ProducerModal.vue'
import ProducerWeekAdjustmentModal from '../../../Components/ProducerWeekAdjustmentModal.vue'
import ProducerWeekDetailModal from '../../../Components/ProducerWeekDetailModal.vue'
import ProducerStatsPanel from '../../../Components/ProducerStatsPanel.vue'
import ProducerPenalizedModal from '../../../Components/ProducerPenalizedModal.vue'

const props = defineProps({
  routes: Array,
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      active: 0,
      inactive: 0,
      without_route: 0,
      penalized: 0,
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
      totals: { producers: 0, days: 0, daily: {}, liters: 0, amount: 0, penalized: 0 },
    }),
  },
})

const searchQuery = ref('')
const showCreateModal = ref(false)
const showDetailModal = ref(false)
const showAdjustmentModal = ref(false)
const showPenalizedModal = ref(false)
const selectedProducer = ref(null)
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

const adjustmentReturnTo = computed(() => {
  const params = new URLSearchParams()
  if (routeFilter.value) params.set('route_id', routeFilter.value)
  if (weekFilter.value) params.set('week', weekFilter.value)
  const query = params.toString()
  return query ? `/producers?${query}` : '/producers'
})

const penalizedCount = computed(() => {
  if (props.report?.totals?.penalized != null) {
    return Number(props.report.totals.penalized)
  }

  return (props.report?.rows || []).filter((row) => row.density_price != null).length
})

const penalizedProducers = computed(() =>
  (props.report?.rows || [])
    .filter((row) => row.density_price != null)
    .slice()
    .sort((a, b) => {
      const routeA = a.route?.code || ''
      const routeB = b.route?.code || ''
      if (routeA !== routeB) return routeA.localeCompare(routeB, 'es')
      return String(a.full_name || '').localeCompare(String(b.full_name || ''), 'es')
    })
)

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

const openDetailModal = (row) => {
  selectedProducer.value = row
  showDetailModal.value = true
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedProducer.value = null
}

const openAdjustmentModal = (row) => {
  selectedProducer.value = row
  showDetailModal.value = false
  showAdjustmentModal.value = true
}

const closeAdjustmentModal = () => {
  showAdjustmentModal.value = false
  selectedProducer.value = null
}

const openAdjustmentFromDetail = (row) => {
  openAdjustmentModal(row)
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
      :penalized="penalizedCount"
      @registrar-cliente="openCreateModal"
      @ver-penalizados="showPenalizedModal = true"
    />

    <ProducerPenalizedModal
      :show="showPenalizedModal"
      :producers="penalizedProducers"
      :week="report.week"
      :base-price="report.price"
      @close="showPenalizedModal = false"
    />

    <div
      v-if="$page.props.flash?.success"
      class="bg-[#E8F8E8] border border-[#34C759] text-[#1D7A32] px-4 py-3 rounded-xl mb-4 print:hidden"
    >
      {{ $page.props.flash.success }}
    </div>

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
          Precio base: {{ money(report.price) }} / L
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
              <th class="px-3 py-3 font-semibold text-right">Pago jueves</th>
              <th class="px-3 py-3 font-semibold text-center print:hidden">Ajuste</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#E5E5E5]">
            <tr
              v-for="row in filteredRows"
              :key="row.id"
              class="hover:bg-[#F5F5F7] cursor-pointer"
              @click="openDetailModal(row)"
            >
              <td class="px-4 py-3 sticky left-0 bg-white">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <span class="block font-medium text-[#007AFF] hover:underline underline-offset-2">
                      {{ row.full_name }}
                    </span>
                    <span class="block text-xs text-[#8E8E93]">{{ row.identity_number || row.phone || '—' }}</span>
                    <span
                      v-if="row.density_price != null || row.advance_amount > 0"
                      class="block text-[11px] text-[#007AFF] mt-0.5"
                    >
                      <span v-if="row.density_price != null">Precio {{ money(row.price) }}/L</span>
                      <span v-if="row.density_price != null && row.advance_amount > 0"> · </span>
                      <span v-if="row.advance_amount > 0">Adelanto {{ money(row.advance_amount) }}</span>
                    </span>
                  </div>
                  <span class="inline-flex items-center px-2 py-1 rounded-lg text-[10px] font-semibold bg-[#E5F1FF] text-[#007AFF] shrink-0 print:hidden">
                    <Eye class="w-3 h-3 mr-1" />
                    Ver
                  </span>
                </div>
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
              <td class="px-3 py-3 text-right tabular-nums font-semibold text-[#1D1D1F]">{{ money(row.amount) }}</td>
              <td class="px-3 py-3 text-center print:hidden" @click.stop>
                <button
                  type="button"
                  @click="openAdjustmentModal(row)"
                  class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#E5F1FF] text-[#007AFF] hover:bg-[#D6E9FF]"
                >
                  <Pencil class="w-3.5 h-3.5 mr-1" />
                  Editar
                </button>
              </td>
            </tr>
            <tr v-if="!filteredRows.length">
              <td :colspan="5 + (report.days?.length || 0)" class="px-4 py-10 text-center text-[#8E8E93]">
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
              <td class="px-3 py-3 text-right tabular-nums">{{ money(report.totals.amount) }}</td>
              <td class="px-3 py-3 print:hidden" />
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

    <ProducerWeekDetailModal
      :show="showDetailModal"
      :producer="selectedProducer"
      :week="report.week"
      :days="report.days"
      :base-price="report.price"
      @close="closeDetailModal"
      @edit-adjustment="openAdjustmentFromDetail"
    />

    <ProducerWeekAdjustmentModal
      :show="showAdjustmentModal"
      :producer="selectedProducer"
      :week-end="report.week.end || weekFilter"
      :base-price="report.price"
      :return-to="adjustmentReturnTo"
      @close="closeAdjustmentModal"
    />
  </AppShell>
</template>
