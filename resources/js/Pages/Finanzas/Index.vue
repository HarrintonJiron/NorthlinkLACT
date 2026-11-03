<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppShell from '../../Components/AppShell.vue'
import FinanzasStatsPanel from '../../Components/FinanzasStatsPanel.vue'
import FinanceTransactionModal from '../../Components/FinanceTransactionModal.vue'
import {
  Search,
  Pencil,
  Wallet,
  Calendar,
  User,
  FileText,
  CircleCheck,
  CircleX,
  ArrowRight,
} from '@lucide/vue'

const props = defineProps({
  initialType: {
    type: String,
    default: null,
  },
  transactions: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
  typeOptions: {
    type: Array,
    default: () => [],
  },
  paymentMethods: {
    type: Array,
    default: () => [],
  },
  pagination: { type: Object, default: () => ({ links: [], total: 0 }) },
})

const searchQuery = ref('')
const typeFilter = ref(props.initialType || 'all')
const showModal = ref(false)
const editingTransaction = ref(null)
const maxVisibleItems = 30

const typeMeta = {
  gasto: { label: 'Gasto', class: 'bg-[#FFF4E5] text-[#FF9500]' },
  pago: { label: 'Pago', class: 'bg-[#FFE5E5] text-[#FF3B30]' },
  ingreso: { label: 'Ingreso', class: 'bg-[#E8F8E8] text-[#1D7A32]' },
}

const paymentMethodLabels = {
  efectivo: 'Efectivo',
  transferencia: 'Transferencia',
  cheque: 'Cheque',
  tarjeta: 'Tarjeta',
  otro: 'Otro',
}

const statusMeta = {
  draft: { label: 'Borrador', class: 'bg-[#F5F5F7] text-[#6E6E73]', next: 'reviewed', action: 'Enviar a revisión' },
  reviewed: { label: 'Revisado', class: 'bg-[#E5F1FF] text-[#007AFF]', next: 'approved', action: 'Aprobar' },
  approved: { label: 'Aprobado', class: 'bg-[#FFF4E5] text-[#B66A00]', next: 'paid', action: 'Marcar realizado' },
  paid: { label: 'Realizado', class: 'bg-[#F1EAFE] text-[#7C3AED]', next: 'reconciled', action: 'Conciliar' },
  reconciled: { label: 'Conciliado', class: 'bg-[#E8F8E8] text-[#1D7A32]' },
  voided: { label: 'Anulado', class: 'bg-[#FFECEB] text-[#C9342E]' },
}

const formatMoney = (value) =>
  `C$ ${Number(value || 0).toLocaleString('es-NI', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`

const formatDate = (value) => {
  if (!value) return '—'
  const date = new Date(`${String(value).slice(0, 10)}T00:00:00`)
  if (Number.isNaN(date.getTime())) return '—'
  return date.toLocaleDateString('es-NI', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

const filteredTransactions = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  let list = props.transactions || []

  if (typeFilter.value !== 'all') {
    list = list.filter((item) => item.type === typeFilter.value)
  }

  if (!query) return list

  return list.filter((item) =>
    [
      item.code,
      item.concept,
      item.description,
      item.reference,
      item.payee,
      item.notes,
      item.category?.name,
      typeMeta[item.type]?.label,
      paymentMethodLabels[item.payment_method],
    ]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(query))
  )
})

const visibleTransactions = computed(() => filteredTransactions.value.slice(0, maxVisibleItems))

const openCreate = () => {
  editingTransaction.value = null
  showModal.value = true
}

const openEdit = (transaction) => {
  editingTransaction.value = transaction
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingTransaction.value = null
}

const handleSubmit = (form) => {
  if (editingTransaction.value?.id) {
    form.put(`/finanzas/transactions/${editingTransaction.value.id}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    })
    return
  }

  form.post('/finanzas/transactions', {
    preserveScroll: true,
    onSuccess: () => closeModal(),
  })
}

const transitionTransaction = (transaction, status) => {
  let reason = null
  if (status === 'voided') {
    reason = window.prompt('Indica el motivo de la anulación (mínimo 8 caracteres):')
    if (!reason) return
  }

  router.patch(`/finanzas/transactions/${transaction.id}/status`, { status, reason }, { preserveScroll: true })
}
</script>

<template>
  <AppShell>
    <div v-if="initialType === 'pago'" class="mb-4 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4">
      <p class="font-display text-lg font-bold text-slate-950">Control de pagos</p>
      <p class="mt-1 text-sm text-slate-600">Esta vista muestra inicialmente los movimientos de tipo pago. Puedes consultar los demás tipos con los filtros.</p>
    </div>

    <FinanzasStatsPanel :stats="stats" @registrar-movimiento="openCreate" />

    <div
      v-if="$page.props.flash?.success"
      class="bg-[#E8F8E8] border border-[#34C759] text-[#1D7A32] px-4 py-3 rounded-xl mb-4"
    >
      {{ $page.props.flash.success }}
    </div>
    <div
      v-if="$page.props.errors?.transaction || $page.props.errors?.status || $page.props.errors?.reason"
      class="bg-[#FFECEB] border border-[#FF3B30] text-[#A5231E] px-4 py-3 rounded-xl mb-4"
    >
      {{ $page.props.errors.transaction || $page.props.errors.status || $page.props.errors.reason }}
    </div>

    <div class="rounded-[28px] border border-[#E5E5E5] bg-white overflow-hidden shadow-sm">
      <div class="p-4 border-b border-[#E5E5E5] flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div class="relative flex-1 lg:max-w-sm">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar concepto, referencia, beneficiario..."
            class="pl-10 pr-4 py-2 bg-[#F5F5F7] border-none rounded-full focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-sm w-full text-[#1D1D1F] placeholder-[#8E8E93]"
          />
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-for="option in [{ value: 'all', label: 'Todos' }, ...typeOptions]"
            :key="option.value"
            type="button"
            @click="typeFilter = option.value"
            class="px-3 py-1.5 rounded-full text-xs font-semibold transition-colors"
            :class="typeFilter === option.value
              ? 'bg-[#007AFF] text-white'
              : 'bg-[#F5F5F7] text-[#6E6E73] hover:bg-[#E5E5E5]'"
          >
            {{ option.label }}
          </button>
          <p class="text-sm text-[#8E8E93] ml-1">
            Mostrando {{ visibleTransactions.length }} de {{ filteredTransactions.length }} movimientos
          </p>
        </div>
      </div>

      <div class="max-h-[38rem] overflow-auto">
        <table class="w-full text-sm">
          <thead class="bg-[#F5F5F7] text-left text-xs uppercase tracking-wide text-[#8E8E93]">
            <tr>
              <th class="px-4 py-3 font-semibold">Movimiento</th>
              <th class="px-3 py-3 font-semibold">Tipo</th>
              <th class="px-3 py-3 font-semibold">Fecha</th>
              <th class="px-3 py-3 font-semibold text-right">Monto</th>
              <th class="px-3 py-3 font-semibold">Detalle</th>
              <th class="px-3 py-3 font-semibold text-center">Estado</th>
              <th class="px-3 py-3 font-semibold text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#E5E5E5]">
            <tr
              v-for="transaction in visibleTransactions"
              :key="transaction.id"
              class="hover:bg-[#F5F5F7]"
            >
              <td class="px-4 py-3">
                <div class="flex items-start gap-3">
                  <div class="bg-[#E5F1FF] p-2.5 rounded-xl shrink-0">
                    <Wallet class="w-4 h-4 text-[#007AFF]" />
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium text-[#1D1D1F]">{{ transaction.concept }}</p>
                    <p class="text-xs text-[#8E8E93]">{{ transaction.code }}</p>
                    <p v-if="transaction.category?.name" class="text-xs text-[#6E6E73] mt-0.5">
                      {{ transaction.category.name }}
                    </p>
                  </div>
                </div>
              </td>
              <td class="px-3 py-3 whitespace-nowrap">
                <span
                  class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold"
                  :class="typeMeta[transaction.type]?.class || 'bg-[#F5F5F7] text-[#8E8E93]'"
                >
                  {{ typeMeta[transaction.type]?.label || transaction.type }}
                </span>
              </td>
              <td class="px-3 py-3 whitespace-nowrap text-[#6E6E73]">
                <span class="inline-flex items-center gap-1.5">
                  <Calendar class="w-3.5 h-3.5 text-[#007AFF]" />
                  {{ formatDate(transaction.transaction_date) }}
                </span>
              </td>
              <td class="px-3 py-3 text-right tabular-nums font-semibold whitespace-nowrap"
                :class="transaction.type === 'ingreso' ? 'text-[#1D7A32]' : 'text-[#1D1D1F]'"
              >
                {{ formatMoney(transaction.amount) }}
              </td>
              <td class="px-3 py-3 min-w-[180px]">
                <div class="space-y-1 text-xs text-[#6E6E73]">
                  <p v-if="transaction.payee" class="inline-flex items-center gap-1 truncate">
                    <User class="w-3.5 h-3.5 shrink-0" />
                    {{ transaction.payee }}
                  </p>
                  <p v-if="transaction.reference" class="inline-flex items-center gap-1 truncate">
                    <FileText class="w-3.5 h-3.5 shrink-0" />
                    {{ transaction.reference }}
                  </p>
                  <p v-if="transaction.payment_method" class="truncate">
                    {{ paymentMethodLabels[transaction.payment_method] || transaction.payment_method }}
                  </p>
                  <p v-if="transaction.description" class="line-clamp-2">{{ transaction.description }}</p>
                </div>
              </td>
              <td class="px-3 py-3 text-center">
                <span
                  class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold"
                  :class="statusMeta[transaction.status]?.class || 'bg-[#F5F5F7] text-[#8E8E93]'"
                >
                  {{ statusMeta[transaction.status]?.label || transaction.status }}
                </span>
                <p v-if="transaction.void_reason" class="mt-1 text-[11px] text-[#C9342E] max-w-36 mx-auto">{{ transaction.void_reason }}</p>
              </td>
              <td class="px-3 py-3">
                <div class="flex items-center justify-end gap-2">
                  <button
                    v-if="transaction.status === 'draft'"
                    type="button"
                    @click="openEdit(transaction)"
                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#E5F1FF] text-[#007AFF] hover:bg-[#D6E9FF]"
                  >
                    <Pencil class="w-3.5 h-3.5 mr-1" />
                    Editar
                  </button>
                  <button
                    v-if="statusMeta[transaction.status]?.next"
                    type="button"
                    @click="transitionTransaction(transaction, statusMeta[transaction.status].next)"
                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#E8F8E8] text-[#1D7A32] hover:bg-[#DDF3DD]"
                  >
                    <CircleCheck class="w-3.5 h-3.5 mr-1" />
                    {{ statusMeta[transaction.status].action }}
                    <ArrowRight class="w-3.5 h-3.5 ml-1" />
                  </button>
                  <button
                    v-if="!['voided', 'reconciled'].includes(transaction.status)"
                    type="button"
                    @click="transitionTransaction(transaction, 'voided')"
                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#FFECEB] text-[#C9342E] hover:bg-[#FFDAD8]"
                  >
                    <CircleX class="w-3.5 h-3.5 mr-1" />
                    Anular
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!filteredTransactions.length">
              <td colspan="7" class="px-4 py-12 text-center text-[#8E8E93]">
                No hay movimientos registrados. Agrega el primero con sus descripciones y montos.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="pagination.links?.length > 3" class="flex items-center justify-between border-t border-[#E5E5E5] px-4 py-3"><p class="text-xs text-[#8E8E93]">{{ pagination.from || 0 }}–{{ pagination.to || 0 }} de {{ pagination.total }}</p><div class="flex gap-1"><Link v-for="link in pagination.links.filter((item) => item.url)" :key="link.label" :href="link.url" :class="['rounded-lg px-3 py-1.5 text-xs', link.active ? 'bg-[#007AFF] text-white' : 'bg-[#F5F5F7] text-[#6E6E73]']" v-html="link.label" /></div></div>
    </div>

    <FinanceTransactionModal
      :show="showModal"
      :transaction="editingTransaction"
      :categories="categories"
      :type-options="typeOptions"
      :payment-methods="paymentMethods"
      @close="closeModal"
      @submit="handleSubmit"
    />
  </AppShell>
</template>
