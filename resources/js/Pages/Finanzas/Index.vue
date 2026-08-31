<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppShell from '../../Components/AppShell.vue'
import FinanzasStatsPanel from '../../Components/FinanzasStatsPanel.vue'
import FinanceTransactionModal from '../../Components/FinanceTransactionModal.vue'
import {
  Search,
  Pencil,
  Power,
  Wallet,
  Calendar,
  User,
  FileText,
} from '@lucide/vue'

const props = defineProps({
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
})

const searchQuery = ref('')
const typeFilter = ref('all')
const showModal = ref(false)
const editingTransaction = ref(null)

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

const toggleTransaction = (transaction) => {
  router.patch(`/finanzas/transactions/${transaction.id}/toggle`, {}, { preserveScroll: true })
}
</script>

<template>
  <AppShell>
    <FinanzasStatsPanel :stats="stats" @registrar-movimiento="openCreate" />

    <div
      v-if="$page.props.flash?.success"
      class="bg-[#E8F8E8] border border-[#34C759] text-[#1D7A32] px-4 py-3 rounded-xl mb-4"
    >
      {{ $page.props.flash.success }}
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
          <p class="text-sm text-[#8E8E93] ml-1">{{ filteredTransactions.length }} movimientos</p>
        </div>
      </div>

      <div class="overflow-x-auto">
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
              v-for="transaction in filteredTransactions"
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
                  :class="transaction.active ? 'bg-[#E8F8E8] text-[#1D7A32]' : 'bg-[#F5F5F7] text-[#8E8E93]'"
                >
                  {{ transaction.active ? 'Activo' : 'Anulado' }}
                </span>
              </td>
              <td class="px-3 py-3">
                <div class="flex items-center justify-end gap-2">
                  <button
                    type="button"
                    @click="openEdit(transaction)"
                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#E5F1FF] text-[#007AFF] hover:bg-[#D6E9FF]"
                  >
                    <Pencil class="w-3.5 h-3.5 mr-1" />
                    Editar
                  </button>
                  <button
                    type="button"
                    @click="toggleTransaction(transaction)"
                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors"
                    :class="transaction.active
                      ? 'bg-[#F5F5F7] text-[#6E6E73] hover:bg-[#E5E5E5]'
                      : 'bg-[#E8F8E8] text-[#1D7A32] hover:bg-[#DDF3DD]'"
                  >
                    <Power class="w-3.5 h-3.5 mr-1" />
                    {{ transaction.active ? 'Anular' : 'Activar' }}
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
