<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Package, Ruler, Hash, Check, X, AlignLeft, Calendar } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  product: {
    type: Object,
    default: null,
  },
  units: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close', 'submit'])

const isEditing = computed(() => Boolean(props.product?.id))

const availableUnits = computed(() => {
  if (!props.product?.unit) return props.units
  const exists = props.units.some((unit) => unit.id === props.product.unit_id)
  if (exists) return props.units
  return [...props.units, props.product.unit]
})

const form = useForm({
  name: '',
  description: '',
  unit_id: '',
  stock: '0',
  min_stock: '0',
  expiration_date: '',
  active: true,
})

const resetForm = () => {
  if (props.product) {
    form.name = props.product.name || ''
    form.description = props.product.description || ''
    form.unit_id = props.product.unit_id ? String(props.product.unit_id) : ''
    form.stock = props.product.stock != null ? String(props.product.stock) : '0'
    form.min_stock = props.product.min_stock != null ? String(props.product.min_stock) : '0'
    form.expiration_date = props.product.expiration_date
      ? String(props.product.expiration_date).slice(0, 10)
      : ''
    form.active = props.product.active ?? true
  } else {
    form.reset()
    form.stock = '0'
    form.min_stock = '0'
    form.expiration_date = ''
    form.active = true
    form.unit_id = props.units[0] ? String(props.units[0].id) : ''
  }
  form.clearErrors()
}

watch(() => props.show, (visible) => {
  if (visible) resetForm()
})

watch(() => props.product, () => {
  if (props.show) resetForm()
})

const submit = () => {
  if (!form.unit_id) {
    form.setError('unit_id', 'Debes seleccionar una unidad de medida.')
    return
  }
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
      <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex items-center justify-between p-5 border-b border-[#E5E5E5] sticky top-0 bg-white z-10">
          <div>
            <p class="text-[11px] tracking-[0.18em] uppercase text-[#007AFF]">Inventario</p>
            <h2 class="text-lg font-display font-bold text-[#1D1D1F]">
              {{ isEditing ? 'Editar producto' : 'Nuevo producto' }}
            </h2>
          </div>
          <button type="button" @click="close" class="p-2 rounded-lg hover:bg-[#F5F5F7] text-[#8E8E93]">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submit" class="p-5 space-y-4">
          <div
            v-if="isEditing && product?.code"
            class="rounded-xl bg-[#F5F5F7] px-3.5 py-2.5 flex items-center gap-2 text-sm"
          >
            <Hash class="w-4 h-4 text-[#8E8E93] shrink-0" />
            <div>
              <p class="text-[11px] text-[#8E8E93]">Código</p>
              <p class="font-semibold text-[#1D1D1F] tabular-nums">{{ product.code }}</p>
            </div>
          </div>
          <p
            v-else
            class="text-xs text-[#8E8E93] rounded-xl bg-[#F5F5F7] px-3.5 py-2.5"
          >
            El código se genera automáticamente al guardar (PRD-0001, PRD-0002…).
          </p>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Nombre</label>
            <div class="relative">
              <Package class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="Ej. Leche cruda, Envase 1L..."
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm"
              />
            </div>
            <p v-if="form.errors.name" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Unidad de medida</label>
            <div class="relative">
              <Ruler class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <select
                v-model="form.unit_id"
                required
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm appearance-none"
              >
                <option value="" disabled>Seleccionar...</option>
                <option v-for="unit in availableUnits" :key="unit.id" :value="String(unit.id)">
                  {{ unit.name }} ({{ unit.symbol || unit.code }})
                </option>
              </select>
            </div>
            <p v-if="form.errors.unit_id" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.unit_id }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Stock actual</label>
              <input
                v-model="form.stock"
                type="number"
                min="0"
                step="0.001"
                class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm"
              />
              <p v-if="form.errors.stock" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.stock }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Stock mínimo</label>
              <input
                v-model="form.min_stock"
                type="number"
                min="0"
                step="0.001"
                class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm"
              />
              <p v-if="form.errors.min_stock" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.min_stock }}</p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Descripción (opcional)</label>
            <div class="relative">
              <AlignLeft class="absolute left-3 top-3 w-4 h-4 text-[#8E8E93]" />
              <textarea
                v-model="form.description"
                rows="2"
                placeholder="Notas del producto..."
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm resize-none"
              />
            </div>
            <p v-if="form.errors.description" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.description }}</p>
          </div>

          <div class="rounded-2xl border border-dashed border-[#E5E5E5] bg-[#FAFAFA] p-4 space-y-3">
            <div>
              <p class="text-[11px] tracking-[0.14em] uppercase text-[#8E8E93]">Opcional</p>
              <p class="text-sm font-semibold text-[#1D1D1F] mt-0.5">Fecha de vencimiento</p>
              <p class="text-xs text-[#8E8E93] mt-0.5">
                Déjala vacía si el producto no vence o no aplica.
              </p>
            </div>
            <div class="relative">
              <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input
                v-model="form.expiration_date"
                type="date"
                class="pl-10 w-full bg-white border border-[#E5E5E5] rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm"
              />
            </div>
            <p v-if="form.errors.expiration_date" class="text-xs text-[#FF3B30]">
              {{ form.errors.expiration_date }}
            </p>
          </div>

          <label v-if="isEditing" class="flex items-center gap-2 text-sm text-[#1D1D1F]">
            <input v-model="form.active" type="checkbox" class="rounded border-[#C7C7CC] text-[#007AFF] focus:ring-[#007AFF]" />
            Producto activo
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
              {{ form.processing ? 'Guardando...' : (isEditing ? 'Guardar cambios' : 'Agregar producto') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
