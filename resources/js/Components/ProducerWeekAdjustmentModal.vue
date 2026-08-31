<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { DollarSign, Droplets, Check, X } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  producer: {
    type: Object,
    default: null,
  },
  weekEnd: {
    type: String,
    required: true,
  },
  basePrice: {
    type: [Number, String],
    default: 0,
  },
  returnTo: {
    type: String,
    default: '/producers',
  },
})

const emit = defineEmits(['close'])

const form = useForm({
  week_end: '',
  density_price: '',
  advance_amount: '',
  notes: '',
  return_to: '',
})

watch(
  () => [props.show, props.producer],
  ([visible]) => {
    if (!visible || !props.producer) return

    form.week_end = props.weekEnd
    form.density_price = props.producer.density_price != null ? String(props.producer.density_price) : ''
    form.advance_amount = props.producer.advance_amount ? String(props.producer.advance_amount) : ''
    form.notes = props.producer.notes || ''
    form.return_to = props.returnTo
    form.clearErrors()
  }
)

const liters = computed(() => Number(props.producer?.liters || 0))
const basePrice = computed(() => Number(props.basePrice || 0))
const appliedPrice = computed(() => {
  const value = parseFloat(form.density_price)
  return Number.isFinite(value) ? value : basePrice.value
})
const advance = computed(() => {
  const value = parseFloat(form.advance_amount)
  return Number.isFinite(value) ? value : 0
})
const gross = computed(() => Math.round(liters.value * appliedPrice.value * 100) / 100)
const net = computed(() => Math.round((gross.value - advance.value) * 100) / 100)

const money = (value) =>
  `C$ ${Number(value || 0).toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const densityHint = computed(() => {
  const avg = props.producer?.avg_density
  if (avg == null) return 'Sin densidades registradas esta semana.'
  if (avg < 25) return `Promedio ${avg} °C · posible agua`
  if (avg > 25) return `Promedio ${avg} °C · tiende a cortarse`
  return `Promedio ${avg} °C · buena leche`
})

const submit = () => {
  form.put(`/producers/${props.producer.id}/week-adjustment`, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => emit('close'),
  })
}

const close = () => emit('close')
</script>

<template>
  <div
    v-if="show && producer"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="close"
  >
    <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
      <div class="flex items-center justify-between p-5 border-b border-[#E5E5E5] sticky top-0 bg-white z-10">
        <div>
          <p class="text-[11px] tracking-[0.18em] uppercase text-[#007AFF]">Ajuste semanal</p>
          <h2 class="text-lg font-display font-bold text-[#1D1D1F]">{{ producer.full_name }}</h2>
        </div>
        <button type="button" @click="close" class="p-2 rounded-lg hover:bg-[#F5F5F7] text-[#8E8E93]">
          <X class="w-5 h-5" />
        </button>
      </div>

      <form @submit.prevent="submit" class="p-5 space-y-5">
        <div class="rounded-2xl bg-[#F5F5F7] p-4 grid grid-cols-2 gap-3 text-sm">
          <div>
            <p class="text-xs text-[#8E8E93]">Litros de la semana</p>
            <p class="font-semibold text-[#1D1D1F]">{{ liters.toLocaleString('es-NI', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) }} L</p>
          </div>
          <div>
            <p class="text-xs text-[#8E8E93]">Precio base</p>
            <p class="font-semibold text-[#1D1D1F]">{{ money(basePrice) }} / L</p>
          </div>
          <div class="col-span-2 flex items-start gap-2">
            <Droplets class="w-4 h-4 text-[#007AFF] mt-0.5 shrink-0" />
            <p class="text-xs text-[#6E6E73]">{{ densityHint }}</p>
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">
            Penalización (C$ / L)
          </label>
          <div class="relative">
            <DollarSign class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="number"
              min="0"
              step="0.01"
              v-model="form.density_price"
              :placeholder="String(basePrice)"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
            />
          </div>
          <p class="text-xs text-[#8E8E93] mt-1">
            Aplica a toda la producción de la semana. Déjalo vacío para usar el precio base.
          </p>
          <p v-if="form.errors.density_price" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.density_price }}</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">
            Adelanto de dinero (C$)
          </label>
          <div class="relative">
            <DollarSign class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="number"
              min="0"
              step="0.01"
              v-model="form.advance_amount"
              placeholder="0.00"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
            />
          </div>
          <p class="text-xs text-[#8E8E93] mt-1">
            Se descuenta del pago del jueves.
          </p>
          <p v-if="form.errors.advance_amount" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.advance_amount }}</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Nota (opcional)</label>
          <textarea
            v-model="form.notes"
            rows="2"
            placeholder="Motivo de la multa o del adelanto..."
            class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] resize-none"
          />
          <p v-if="form.errors.notes" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.notes }}</p>
        </div>

        <div class="rounded-2xl border border-[#E5E5E5] p-4 space-y-2 text-sm">
          <div class="flex justify-between gap-3">
            <span class="text-[#8E8E93]">Precio aplicado</span>
            <span class="font-medium text-[#1D1D1F]">{{ money(appliedPrice) }} / L</span>
          </div>
          <div class="flex justify-between gap-3">
            <span class="text-[#8E8E93]">Bruto (litros × precio)</span>
            <span class="font-medium text-[#1D1D1F]">{{ money(gross) }}</span>
          </div>
          <div class="flex justify-between gap-3">
            <span class="text-[#8E8E93]">Adelanto</span>
            <span class="font-medium text-[#FF3B30]">− {{ money(advance) }}</span>
          </div>
          <div class="flex justify-between gap-3 pt-2 border-t border-[#E5E5E5]">
            <span class="font-semibold text-[#1D1D1F]">Pago jueves</span>
            <span class="font-bold text-[#1D1D1F]">{{ money(net) }}</span>
          </div>
        </div>

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
            {{ form.processing ? 'Guardando...' : 'Guardar ajustes' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
