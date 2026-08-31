<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  Wallet,
  Hash,
  Check,
  X,
  AlignLeft,
  Calendar,
  User,
  FileText,
  Tag,
} from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  transaction: {
    type: Object,
    default: null,
  },
  categories: {
    type: Array,
    default: () => [],
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

const emit = defineEmits(['close', 'submit'])

const isEditing = computed(() => Boolean(props.transaction?.id))

const form = useForm({
  type: 'gasto',
  category_id: '',
  concept: '',
  description: '',
  amount: '',
  transaction_date: new Date().toISOString().slice(0, 10),
  payment_method: '',
  reference: '',
  payee: '',
  notes: '',
  active: true,
})

const filteredCategories = computed(() => {
  if (!form.type) return props.categories
  return props.categories.filter((category) => category.type === form.type || category.code === 'OTRO')
})

const payeeLabel = computed(() => {
  if (form.type === 'ingreso') return 'Cliente / origen'
  if (form.type === 'pago') return 'Beneficiario'
  return 'Proveedor / beneficiario'
})

const resetForm = () => {
  if (props.transaction) {
    form.type = props.transaction.type || 'gasto'
    form.category_id = props.transaction.category_id ? String(props.transaction.category_id) : ''
    form.concept = props.transaction.concept || ''
    form.description = props.transaction.description || ''
    form.amount = props.transaction.amount != null ? String(props.transaction.amount) : ''
    form.transaction_date = props.transaction.transaction_date
      ? String(props.transaction.transaction_date).slice(0, 10)
      : new Date().toISOString().slice(0, 10)
    form.payment_method = props.transaction.payment_method || ''
    form.reference = props.transaction.reference || ''
    form.payee = props.transaction.payee || ''
    form.notes = props.transaction.notes || ''
    form.active = props.transaction.active ?? true
  } else {
    form.reset()
    form.type = 'gasto'
    form.transaction_date = new Date().toISOString().slice(0, 10)
    form.active = true
    form.category_id = ''
    form.payment_method = ''
  }
  form.clearErrors()
}

watch(() => props.show, (visible) => {
  if (visible) resetForm()
})

watch(() => props.transaction, () => {
  if (props.show) resetForm()
})

watch(() => form.type, () => {
  if (!props.show || isEditing.value) return
  const exists = filteredCategories.value.some((category) => String(category.id) === form.category_id)
  if (!exists) {
    form.category_id = filteredCategories.value[0] ? String(filteredCategories.value[0].id) : ''
  }
})

const submit = () => {
  emit('submit', form)
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
      <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex items-center justify-between p-5 border-b border-[#E5E5E5] sticky top-0 bg-white z-10">
          <div>
            <p class="text-[11px] tracking-[0.18em] uppercase text-[#007AFF]">Finanzas</p>
            <h2 class="text-lg font-display font-bold text-[#1D1D1F]">
              {{ isEditing ? 'Editar movimiento' : 'Nuevo movimiento' }}
            </h2>
          </div>
          <button type="button" @click="close" class="p-2 rounded-lg hover:bg-[#F5F5F7] text-[#8E8E93]">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submit" class="p-5 space-y-4">
          <div
            v-if="isEditing && transaction?.code"
            class="rounded-xl bg-[#F5F5F7] px-3.5 py-2.5 flex items-center gap-2 text-sm"
          >
            <Hash class="w-4 h-4 text-[#8E8E93] shrink-0" />
            <div>
              <p class="text-[11px] text-[#8E8E93]">Código</p>
              <p class="font-semibold text-[#1D1D1F] tabular-nums">{{ transaction.code }}</p>
            </div>
          </div>
          <p v-else class="text-xs text-[#8E8E93] rounded-xl bg-[#F5F5F7] px-3.5 py-2.5">
            El código se genera automáticamente al guardar (FIN-0001, FIN-0002…).
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <label
              v-for="option in typeOptions"
              :key="option.value"
              class="flex items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-semibold cursor-pointer transition-colors"
              :class="form.type === option.value
                ? 'border-[#007AFF] bg-[#E5F1FF] text-[#007AFF]'
                : 'border-[#E5E5E5] bg-[#FAFAFA] text-[#6E6E73] hover:bg-[#F5F5F7]'"
            >
              <input v-model="form.type" type="radio" :value="option.value" class="sr-only" />
              {{ option.label }}
            </label>
          </div>
          <p v-if="form.errors.type" class="text-xs text-[#FF3B30]">{{ form.errors.type }}</p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Concepto</label>
              <div class="relative">
                <Wallet class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
                <input
                  v-model="form.concept"
                  type="text"
                  required
                  placeholder="Ej. Pago combustible ruta norte"
                  class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm"
                />
              </div>
              <p v-if="form.errors.concept" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.concept }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Monto (C$)</label>
              <input
                v-model="form.amount"
                type="number"
                min="0.01"
                step="0.01"
                required
                placeholder="0.00"
                class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm tabular-nums"
              />
              <p v-if="form.errors.amount" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.amount }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Fecha</label>
              <div class="relative">
                <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
                <input
                  v-model="form.transaction_date"
                  type="date"
                  required
                  class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm"
                />
              </div>
              <p v-if="form.errors.transaction_date" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.transaction_date }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Categoría</label>
              <div class="relative">
                <Tag class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
                <select
                  v-model="form.category_id"
                  class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm appearance-none"
                >
                  <option value="">Sin categoría</option>
                  <option v-for="category in filteredCategories" :key="category.id" :value="String(category.id)">
                    {{ category.name }}
                  </option>
                </select>
              </div>
              <p v-if="form.errors.category_id" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.category_id }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Método de pago</label>
              <select
                v-model="form.payment_method"
                class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm appearance-none"
              >
                <option value="">Seleccionar...</option>
                <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                  {{ method.label }}
                </option>
              </select>
              <p v-if="form.errors.payment_method" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.payment_method }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">{{ payeeLabel }}</label>
              <div class="relative">
                <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
                <input
                  v-model="form.payee"
                  type="text"
                  placeholder="Nombre del proveedor, cliente o beneficiario"
                  class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm"
                />
              </div>
              <p v-if="form.errors.payee" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.payee }}</p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Referencia / No. documento</label>
            <div class="relative">
              <FileText class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input
                v-model="form.reference"
                type="text"
                placeholder="Factura, recibo, transferencia..."
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm"
              />
            </div>
            <p v-if="form.errors.reference" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.reference }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Descripción</label>
            <div class="relative">
              <AlignLeft class="absolute left-3 top-3 w-4 h-4 text-[#8E8E93]" />
              <textarea
                v-model="form.description"
                rows="2"
                placeholder="Detalle del gasto, pago o ingreso..."
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm resize-none"
              />
            </div>
            <p v-if="form.errors.description" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.description }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Notas internas (opcional)</label>
            <textarea
              v-model="form.notes"
              rows="2"
              placeholder="Observaciones adicionales..."
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm resize-none"
            />
            <p v-if="form.errors.notes" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.notes }}</p>
          </div>

          <label v-if="isEditing" class="flex items-center gap-2 text-sm text-[#1D1D1F]">
            <input v-model="form.active" type="checkbox" class="rounded border-[#C7C7CC] text-[#007AFF] focus:ring-[#007AFF]" />
            Movimiento activo
          </label>

          <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
            <button
              type="button"
              @click="close"
              class="px-4 py-2.5 border border-[#E5E5E5] rounded-xl text-sm font-medium text-[#1D1D1F] hover:bg-[#F5F5F7]"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex items-center justify-center px-4 py-2.5 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-50 text-sm font-semibold"
            >
              <Check v-if="!form.processing" class="w-4 h-4 mr-2" />
              {{ form.processing ? 'Guardando...' : (isEditing ? 'Guardar cambios' : 'Registrar movimiento') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
