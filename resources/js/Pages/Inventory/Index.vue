<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppShell from '../../Components/AppShell.vue'
import InventoryProductModal from '../../Components/InventoryProductModal.vue'
import InventoryBulkProductModal from '../../Components/InventoryBulkProductModal.vue'
import InventoryStatsPanel from '../../Components/InventoryStatsPanel.vue'
import InventoryLowStockModal from '../../Components/InventoryLowStockModal.vue'
import {
  Package,
  Search,
  Pencil,
  Power,
  AlertTriangle,
  Ruler,
  Calendar,
} from '@lucide/vue'

const props = defineProps({
  products: {
    type: Array,
    default: () => [],
  },
  units: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      active: 0,
      inactive: 0,
      low_stock: 0,
      zero_stock: 0,
      units: 0,
      new_this_month: 0,
      monthly: [],
      by_unit: [],
    }),
  },
})

const searchQuery = ref('')
const showModal = ref(false)
const showBulkModal = ref(false)
const showLowStockModal = ref(false)
const editingProduct = ref(null)

const filteredProducts = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const list = props.products || []
  if (!query) return list

  return list.filter((product) =>
    [
      product.code,
      product.name,
      product.description,
      product.unit?.name,
      product.unit?.code,
      product.unit?.symbol,
    ]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(query))
  )
})

const lowStockProducts = computed(() =>
  (props.products || [])
    .filter((product) => product.active && Number(product.stock) <= Number(product.min_stock))
    .slice()
    .sort((a, b) => Number(a.stock) - Number(b.stock))
)

const formatQty = (value) =>
  Number(value || 0).toLocaleString('es-NI', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 3,
  })

const formatDate = (value) => {
  if (!value) return null
  const date = new Date(`${String(value).slice(0, 10)}T00:00:00`)
  if (Number.isNaN(date.getTime())) return null
  return date.toLocaleDateString('es-NI', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

const isExpired = (product) => {
  if (!product.expiration_date) return false
  const date = new Date(`${String(product.expiration_date).slice(0, 10)}T00:00:00`)
  if (Number.isNaN(date.getTime())) return false
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return date < today
}

const isLowStock = (product) =>
  product.active && Number(product.stock) <= Number(product.min_stock)

const openCreate = () => {
  editingProduct.value = null
  showModal.value = true
}

const openEdit = (product) => {
  editingProduct.value = product
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingProduct.value = null
}

const handleSubmit = (form) => {
  if (editingProduct.value?.id) {
    form.put(`/inventory/products/${editingProduct.value.id}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    })
    return
  }

  form.post('/inventory/products', {
    preserveScroll: true,
    onSuccess: () => closeModal(),
  })
}

const toggleProduct = (product) => {
  router.patch(`/inventory/products/${product.id}/toggle`, {}, { preserveScroll: true })
}
</script>

<template>
  <AppShell>
    <InventoryStatsPanel
      :stats="stats"
      @agregar-producto="openCreate"
      @carga-masiva="showBulkModal = true"
      @ver-stock-bajo="showLowStockModal = true"
    />

    <div
      v-if="$page.props.flash?.success"
      class="bg-[#E8F8E8] border border-[#34C759] text-[#1D7A32] px-4 py-3 rounded-xl mb-4"
    >
      {{ $page.props.flash.success }}
    </div>

    <div class="rounded-[28px] border border-[#E5E5E5] bg-white overflow-hidden shadow-sm">
      <div class="p-4 border-b border-[#E5E5E5] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="relative flex-1 sm:max-w-xs">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar producto o unidad..."
            class="pl-10 pr-4 py-2 bg-[#F5F5F7] border-none rounded-full focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-sm w-full text-[#1D1D1F] placeholder-[#8E8E93]"
          />
        </div>
        <p class="text-sm text-[#8E8E93]">{{ filteredProducts.length }} productos</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-[#F5F5F7] text-left text-xs uppercase tracking-wide text-[#8E8E93]">
            <tr>
              <th class="px-4 py-3 font-semibold">Producto</th>
              <th class="px-3 py-3 font-semibold">Unidad</th>
              <th class="px-3 py-3 font-semibold text-right">Stock</th>
              <th class="px-3 py-3 font-semibold text-right">Mínimo</th>
              <th class="px-3 py-3 font-semibold text-center">Estado</th>
              <th class="px-3 py-3 font-semibold text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#E5E5E5]">
            <tr
              v-for="product in filteredProducts"
              :key="product.id"
              class="hover:bg-[#F5F5F7]"
            >
              <td class="px-4 py-3">
                <div class="flex items-start gap-3">
                  <div class="bg-[#E5F1FF] p-2.5 rounded-xl shrink-0">
                    <Package class="w-4 h-4 text-[#007AFF]" />
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium text-[#1D1D1F]">{{ product.name }}</p>
                    <p class="text-xs text-[#8E8E93]">{{ product.code }}</p>
                    <p v-if="product.description" class="text-xs text-[#6E6E73] mt-0.5 line-clamp-1">
                      {{ product.description }}
                    </p>
                    <p
                      v-if="formatDate(product.expiration_date)"
                      class="inline-flex items-center gap-1 text-xs mt-1"
                      :class="isExpired(product) ? 'text-[#FF3B30]' : 'text-[#6E6E73]'"
                    >
                      <Calendar class="w-3 h-3 shrink-0" />
                      Vence {{ formatDate(product.expiration_date) }}
                      <span v-if="isExpired(product)" class="font-semibold">(vencido)</span>
                    </p>
                  </div>
                </div>
              </td>
              <td class="px-3 py-3 whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 text-[#6E6E73]">
                  <Ruler class="w-3.5 h-3.5 text-[#007AFF]" />
                  {{ product.unit?.name || '—' }}
                  <span class="text-[#8E8E93]">({{ product.unit?.symbol || product.unit?.code }})</span>
                </span>
              </td>
              <td class="px-3 py-3 text-right tabular-nums font-medium">
                <span :class="isLowStock(product) ? 'text-[#FF3B30]' : 'text-[#1D1D1F]'">
                  {{ formatQty(product.stock) }}
                </span>
                <AlertTriangle
                  v-if="isLowStock(product)"
                  class="inline w-3.5 h-3.5 text-[#FF3B30] ml-1 -mt-0.5"
                />
              </td>
              <td class="px-3 py-3 text-right tabular-nums text-[#6E6E73]">
                {{ formatQty(product.min_stock) }}
              </td>
              <td class="px-3 py-3 text-center">
                <span
                  class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold"
                  :class="product.active ? 'bg-[#E8F8E8] text-[#1D7A32]' : 'bg-[#F5F5F7] text-[#8E8E93]'"
                >
                  {{ product.active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-3 py-3">
                <div class="flex items-center justify-end gap-2">
                  <button
                    type="button"
                    @click="openEdit(product)"
                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#E5F1FF] text-[#007AFF] hover:bg-[#D6E9FF]"
                  >
                    <Pencil class="w-3.5 h-3.5 mr-1" />
                    Editar
                  </button>
                  <button
                    type="button"
                    @click="toggleProduct(product)"
                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors"
                    :class="product.active
                      ? 'bg-[#F5F5F7] text-[#6E6E73] hover:bg-[#E5E5E5]'
                      : 'bg-[#E8F8E8] text-[#1D7A32] hover:bg-[#DDF3DD]'"
                  >
                    <Power class="w-3.5 h-3.5 mr-1" />
                    {{ product.active ? 'Inhabilitar' : 'Habilitar' }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!filteredProducts.length">
              <td colspan="6" class="px-4 py-12 text-center text-[#8E8E93]">
                No hay productos. Agrega el primero con su unidad de medida.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <InventoryProductModal
      :show="showModal"
      :product="editingProduct"
      :units="units"
      @close="closeModal"
      @submit="handleSubmit"
    />

    <InventoryBulkProductModal
      :show="showBulkModal"
      :units="units"
      @close="showBulkModal = false"
    />

    <InventoryLowStockModal
      :show="showLowStockModal"
      :products="lowStockProducts"
      @close="showLowStockModal = false"
    />
  </AppShell>
</template>
