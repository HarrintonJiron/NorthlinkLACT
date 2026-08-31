<script setup>
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Check, X, Plus, Trash2, Upload, Package } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  units: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close'])

const defaultUnitId = computed(() => (props.units[0] ? String(props.units[0].id) : ''))

const emptyRow = () => ({
  name: '',
  unit_id: defaultUnitId.value,
  stock: '0',
  min_stock: '0',
  description: '',
})

const rows = ref([emptyRow(), emptyRow(), emptyRow()])
const submittedIndexMap = ref([])

const form = useForm({
  products: [],
})

watch(
  () => props.show,
  (visible) => {
    if (!visible) return
    rows.value = [emptyRow(), emptyRow(), emptyRow()]
    submittedIndexMap.value = []
    form.clearErrors()
  }
)

const validRows = computed(() =>
  rows.value.filter((row) => row.name.trim() && row.unit_id)
)

const addRow = () => {
  if (rows.value.length >= 50) return
  rows.value.push(emptyRow())
}

const removeRow = (index) => {
  if (rows.value.length <= 1) {
    rows.value = [emptyRow()]
    return
  }
  rows.value.splice(index, 1)
}

const parseQty = (value) => {
  if (value === '') return 0
  const parsed = Number(value)
  return Number.isFinite(parsed) && parsed >= 0 ? parsed : 0
}

const rowError = (displayIndex, field) => {
  const serverIndex = submittedIndexMap.value.indexOf(displayIndex)
  if (serverIndex === -1) return null
  return form.errors[`products.${serverIndex}.${field}`]
}

const submit = () => {
  const indexMap = []
  const products = []

  rows.value.forEach((row, index) => {
    if (!row.name.trim() || !row.unit_id) return

    indexMap.push(index)
    products.push({
      name: row.name.trim(),
      unit_id: Number(row.unit_id),
      stock: parseQty(row.stock),
      min_stock: parseQty(row.min_stock),
      description: row.description.trim() || null,
    })
  })

  if (!products.length) {
    form.setError('products', 'Agrega al menos un producto con nombre y unidad.')
    return
  }

  submittedIndexMap.value = indexMap
  form.products = products

  form.post('/inventory/products/bulk', {
    preserveScroll: true,
    onSuccess: () => emit('close'),
  })
}

const close = () => emit('close')
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
      @click.self="close"
    >
      <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col">
        <div class="flex items-start justify-between gap-3 p-5 border-b border-[#E5E5E5] shrink-0">
          <div>
            <p class="text-[11px] tracking-[0.18em] uppercase text-[#007AFF]">Inventario</p>
            <h2 class="text-lg font-display font-bold text-[#1D1D1F]">Carga masiva de productos</h2>
            <p class="text-xs text-[#8E8E93] mt-0.5">
              Cada producto recibe código automático (PRD-0001, PRD-0002…). Máximo 50 por carga.
            </p>
          </div>
          <button type="button" @click="close" class="p-2 rounded-lg hover:bg-[#F5F5F7] text-[#8E8E93] shrink-0">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submit" class="flex flex-col min-h-0 flex-1">
          <div class="overflow-auto p-5 space-y-3">
            <p v-if="form.errors.products" class="text-xs text-[#FF3B30]">{{ form.errors.products }}</p>

            <div class="overflow-x-auto rounded-2xl border border-[#E5E5E5]">
              <table class="w-full text-sm min-w-[720px]">
                <thead class="bg-[#F5F5F7] text-left text-[11px] uppercase tracking-wide text-[#8E8E93]">
                  <tr>
                    <th class="px-3 py-2.5 font-semibold w-8">#</th>
                    <th class="px-3 py-2.5 font-semibold">Nombre</th>
                    <th class="px-3 py-2.5 font-semibold w-40">Unidad</th>
                    <th class="px-3 py-2.5 font-semibold w-24 text-right">Stock</th>
                    <th class="px-3 py-2.5 font-semibold w-24 text-right">Mínimo</th>
                    <th class="px-3 py-2.5 font-semibold">Descripción</th>
                    <th class="px-3 py-2.5 font-semibold w-10"></th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E5E5]">
                  <tr v-for="(row, index) in rows" :key="index" class="bg-white">
                    <td class="px-3 py-2 text-[#8E8E93] tabular-nums">{{ index + 1 }}</td>
                    <td class="px-3 py-2">
                      <input
                        v-model="row.name"
                        type="text"
                        placeholder="Nombre del producto"
                        class="w-full bg-[#F5F5F7] border-none rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2 px-2.5 text-sm"
                      />
                      <p v-if="rowError(index, 'name')" class="text-[11px] text-[#FF3B30] mt-1">
                        {{ rowError(index, 'name') }}
                      </p>
                    </td>
                    <td class="px-3 py-2">
                      <select
                        v-model="row.unit_id"
                        class="w-full bg-[#F5F5F7] border-none rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2 px-2.5 text-sm"
                      >
                        <option value="" disabled>Unidad</option>
                        <option v-for="unit in units" :key="unit.id" :value="String(unit.id)">
                          {{ unit.symbol || unit.code }}
                        </option>
                      </select>
                      <p v-if="rowError(index, 'unit_id')" class="text-[11px] text-[#FF3B30] mt-1">
                        {{ rowError(index, 'unit_id') }}
                      </p>
                    </td>
                    <td class="px-3 py-2">
                      <input
                        v-model="row.stock"
                        type="number"
                        min="0"
                        step="0.001"
                        class="w-full bg-[#F5F5F7] border-none rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2 px-2.5 text-sm text-right tabular-nums"
                      />
                    </td>
                    <td class="px-3 py-2">
                      <input
                        v-model="row.min_stock"
                        type="number"
                        min="0"
                        step="0.001"
                        class="w-full bg-[#F5F5F7] border-none rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2 px-2.5 text-sm text-right tabular-nums"
                      />
                    </td>
                    <td class="px-3 py-2">
                      <input
                        v-model="row.description"
                        type="text"
                        placeholder="Opcional"
                        class="w-full bg-[#F5F5F7] border-none rounded-lg focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2 px-2.5 text-sm"
                      />
                    </td>
                    <td class="px-3 py-2 text-center">
                      <button
                        type="button"
                        @click="removeRow(index)"
                        class="p-1.5 rounded-lg text-[#8E8E93] hover:bg-[#FFE5E5] hover:text-[#FF3B30]"
                        title="Quitar fila"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <button
              type="button"
              @click="addRow"
              :disabled="rows.length >= 50"
              class="inline-flex items-center gap-2 text-sm font-semibold text-[#007AFF] hover:text-[#0056CC] disabled:opacity-40"
            >
              <Plus class="w-4 h-4" />
              Agregar fila
            </button>
          </div>

          <div class="p-5 border-t border-[#E5E5E5] shrink-0 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-xs text-[#8E8E93] flex items-center gap-1.5">
              <Package class="w-3.5 h-3.5" />
              {{ validRows.length }} producto{{ validRows.length === 1 ? '' : 's' }} listo{{ validRows.length === 1 ? '' : 's' }} para guardar
            </p>
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
              <button
                type="button"
                @click="close"
                class="px-4 py-2.5 border border-[#E5E5E5] rounded-xl text-sm font-medium text-[#1D1D1F] hover:bg-[#F5F5F7]"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="form.processing || !validRows.length"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-50 text-sm font-semibold"
              >
                <Upload v-if="!form.processing" class="w-4 h-4 mr-2" />
                {{ form.processing ? 'Guardando...' : `Guardar ${validRows.length || ''} producto${validRows.length === 1 ? '' : 's'}` }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
