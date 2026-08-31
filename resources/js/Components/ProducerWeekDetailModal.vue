<script setup>
import { computed } from 'vue'
import {
  X,
  Phone,
  CreditCard,
  MapPin,
  Truck,
  Droplets,
  Pencil,
} from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  producer: {
    type: Object,
    default: null,
  },
  week: {
    type: Object,
    default: () => ({}),
  },
  days: {
    type: Array,
    default: () => [],
  },
  basePrice: {
    type: [Number, String],
    default: 0,
  },
})

const emit = defineEmits(['close', 'edit-adjustment'])

const money = (value) =>
  `C$ ${Number(value || 0).toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const liters = (value) =>
  `${Number(value || 0).toLocaleString('es-NI', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} L`

const dayLiters = (value) => {
  if (!value) return '—'
  return Number(value).toLocaleString('es-NI', { minimumFractionDigits: 1, maximumFractionDigits: 1 })
}

const location = computed(() => {
  if (!props.producer) return '—'
  return [props.producer.community, props.producer.municipality, props.producer.department]
    .filter(Boolean)
    .join(' · ') || '—'
})

const densityHint = computed(() => {
  const avg = props.producer?.avg_density
  if (avg == null) return 'Sin densidades registradas esta semana'
  if (avg < 25) return `Promedio ${avg} °C · posible agua`
  if (avg > 25) return `Promedio ${avg} °C · tiende a cortarse`
  return `Promedio ${avg} °C · buena leche`
})

const close = () => emit('close')
const editAdjustment = () => emit('edit-adjustment', props.producer)
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show && producer"
      class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
      @click.self="close"
    >
      <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex items-start justify-between gap-3 p-5 border-b border-[#E5E5E5] sticky top-0 bg-white z-10">
          <div class="min-w-0">
            <p class="text-[11px] tracking-[0.18em] uppercase text-[#007AFF]">Ficha del productor</p>
            <h2 class="text-lg font-display font-bold text-[#1D1D1F] truncate">{{ producer.full_name }}</h2>
            <p class="text-xs text-[#8E8E93] mt-0.5">
              {{ producer.code || 'Sin código' }}
              <span
                class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                :class="producer.active ? 'bg-[#E8F8E8] text-[#1D7A32]' : 'bg-[#F5F5F7] text-[#8E8E93]'"
              >
                {{ producer.active ? 'Activo' : 'Inactivo' }}
              </span>
            </p>
          </div>
          <button type="button" @click="close" class="p-2 rounded-lg hover:bg-[#F5F5F7] text-[#8E8E93] shrink-0">
            <X class="w-5 h-5" />
          </button>
        </div>

      <div class="p-5 space-y-5">
        <section class="space-y-3">
          <p class="text-[11px] tracking-[0.14em] uppercase text-[#8E8E93] font-semibold">Datos generales</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="rounded-2xl bg-[#F5F5F7] p-3.5 flex gap-3">
              <CreditCard class="w-4 h-4 text-[#007AFF] mt-0.5 shrink-0" />
              <div class="min-w-0">
                <p class="text-[11px] text-[#8E8E93]">Cédula</p>
                <p class="text-sm font-medium text-[#1D1D1F] break-all">{{ producer.identity_number || '—' }}</p>
              </div>
            </div>
            <div class="rounded-2xl bg-[#F5F5F7] p-3.5 flex gap-3">
              <Phone class="w-4 h-4 text-[#007AFF] mt-0.5 shrink-0" />
              <div class="min-w-0">
                <p class="text-[11px] text-[#8E8E93]">Teléfono</p>
                <p class="text-sm font-medium text-[#1D1D1F]">{{ producer.phone || '—' }}</p>
              </div>
            </div>
            <div class="rounded-2xl bg-[#F5F5F7] p-3.5 flex gap-3 sm:col-span-2">
              <Truck class="w-4 h-4 text-[#007AFF] mt-0.5 shrink-0" />
              <div class="min-w-0">
                <p class="text-[11px] text-[#8E8E93]">Ruta</p>
                <p class="text-sm font-medium text-[#1D1D1F]">
                  <template v-if="producer.route">
                    {{ producer.route.code }} — {{ producer.route.name }}
                  </template>
                  <template v-else>—</template>
                </p>
              </div>
            </div>
            <div class="rounded-2xl bg-[#F5F5F7] p-3.5 flex gap-3 sm:col-span-2">
              <MapPin class="w-4 h-4 text-[#007AFF] mt-0.5 shrink-0" />
              <div class="min-w-0">
                <p class="text-[11px] text-[#8E8E93]">Ubicación</p>
                <p class="text-sm font-medium text-[#1D1D1F]">{{ location }}</p>
                <p v-if="producer.address" class="text-xs text-[#6E6E73] mt-1">{{ producer.address }}</p>
              </div>
            </div>
          </div>
        </section>

        <section class="space-y-3">
          <div class="flex items-center justify-between gap-2">
            <p class="text-[11px] tracking-[0.14em] uppercase text-[#8E8E93] font-semibold">Semana de pago</p>
            <p class="text-[11px] text-[#8E8E93]">
              {{ week.start }} → {{ week.end }}
            </p>
          </div>

          <div class="rounded-2xl border border-[#E5E5E5] p-4 grid grid-cols-2 gap-3 text-sm">
            <div>
              <p class="text-xs text-[#8E8E93]">Litros</p>
              <p class="font-semibold text-[#1D1D1F]">{{ liters(producer.liters) }}</p>
            </div>
            <div>
              <p class="text-xs text-[#8E8E93]">Días con acopio</p>
              <p class="font-semibold text-[#1D1D1F]">{{ producer.days || 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-[#8E8E93]">Precio aplicado</p>
              <p class="font-semibold text-[#1D1D1F]">{{ money(producer.price) }} / L</p>
              <p v-if="producer.density_price != null" class="text-[11px] text-[#007AFF]">
                Multa densidad (base {{ money(basePrice) }})
              </p>
            </div>
            <div>
              <p class="text-xs text-[#8E8E93]">Adelanto</p>
              <p class="font-semibold" :class="producer.advance_amount > 0 ? 'text-[#FF3B30]' : 'text-[#1D1D1F]'">
                {{ money(producer.advance_amount) }}
              </p>
            </div>
            <div class="col-span-2 flex items-start gap-2 pt-1 border-t border-[#E5E5E5]">
              <Droplets class="w-4 h-4 text-[#007AFF] mt-0.5 shrink-0" />
              <p class="text-xs text-[#6E6E73]">{{ densityHint }}</p>
            </div>
            <div class="col-span-2 flex justify-between items-center pt-1 border-t border-[#E5E5E5]">
              <div>
                <p class="text-xs text-[#8E8E93]">Bruto</p>
                <p class="text-sm font-medium text-[#1D1D1F]">{{ money(producer.gross_amount) }}</p>
              </div>
              <div class="text-right">
                <p class="text-xs text-[#8E8E93]">Pago jueves {{ week.pay_day }}</p>
                <p class="text-base font-bold text-[#1D1D1F]">{{ money(producer.amount) }}</p>
              </div>
            </div>
            <p v-if="producer.notes" class="col-span-2 text-xs text-[#6E6E73] bg-[#F5F5F7] rounded-xl px-3 py-2">
              {{ producer.notes }}
            </p>
          </div>

          <div class="overflow-x-auto -mx-1 px-1">
            <div class="flex gap-2 min-w-max">
              <div
                v-for="day in days"
                :key="day.date"
                class="rounded-xl bg-[#F5F5F7] px-3 py-2 text-center min-w-[3.25rem]"
              >
                <p class="text-[10px] uppercase text-[#8E8E93]">{{ day.label }}</p>
                <p class="text-[10px] text-[#C7C7CC]">{{ day.day }}</p>
                <p
                  class="text-xs font-semibold tabular-nums mt-0.5"
                  :class="producer.daily?.[day.date] ? 'text-[#1D1D1F]' : 'text-[#C7C7CC]'"
                >
                  {{ dayLiters(producer.daily?.[day.date]) }}
                </p>
              </div>
            </div>
          </div>
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-1">
          <button
            type="button"
            @click="close"
            class="px-4 py-2.5 border border-[#E5E5E5] rounded-xl text-sm font-medium text-[#1D1D1F] hover:bg-[#F5F5F7]"
          >
            Cerrar
          </button>
          <button
            type="button"
            @click="editAdjustment"
            class="inline-flex items-center justify-center px-4 py-2.5 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] text-sm font-semibold"
          >
            <Pencil class="w-4 h-4 mr-2" />
            Ajustar semana
          </button>
        </div>
      </div>
    </div>
    </div>
  </Teleport>
</template>
