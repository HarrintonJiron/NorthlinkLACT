<script setup>
import { computed } from 'vue'
import { X, AlertTriangle, Truck } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  producers: {
    type: Array,
    default: () => [],
  },
  week: {
    type: Object,
    default: () => ({}),
  },
  basePrice: {
    type: [Number, String],
    default: 0,
  },
})

const emit = defineEmits(['close'])

const money = (value) =>
  `C$ ${Number(value || 0).toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

const grouped = computed(() => {
  const map = new Map()

  for (const producer of props.producers) {
    const key = producer.route?.id ? String(producer.route.id) : 'none'
    const label = producer.route
      ? `${producer.route.code} — ${producer.route.name}`
      : 'Sin ruta'

    if (!map.has(key)) {
      map.set(key, { key, label, producers: [] })
    }
    map.get(key).producers.push(producer)
  }

  return [...map.values()].sort((a, b) => a.label.localeCompare(b.label, 'es'))
})

const close = () => emit('close')
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
      @click.self="close"
    >
      <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-hidden shadow-2xl flex flex-col">
        <div class="flex items-start justify-between gap-3 p-5 border-b border-[#E5E5E5] shrink-0">
          <div class="min-w-0">
            <p class="text-[11px] tracking-[0.18em] uppercase text-[#FF3B30]">Penalizaciones</p>
            <h2 class="text-lg font-display font-bold text-[#1D1D1F]">Clientes penalizados</h2>
            <p class="text-xs text-[#8E8E93] mt-0.5">
              Semana {{ week.start }} → {{ week.end }} · {{ producers.length }} cliente{{ producers.length === 1 ? '' : 's' }}
            </p>
          </div>
          <button type="button" @click="close" class="p-2 rounded-lg hover:bg-[#F5F5F7] text-[#8E8E93] shrink-0">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="overflow-y-auto p-5 space-y-4">
          <div
            v-if="!producers.length"
            class="rounded-2xl bg-[#F5F5F7] px-4 py-8 text-center text-sm text-[#8E8E93]"
          >
            No hay clientes penalizados en esta semana.
          </div>

          <section v-for="group in grouped" :key="group.key" class="space-y-2">
            <div class="flex items-center gap-2 text-xs font-semibold text-[#6E6E73]">
              <Truck class="w-3.5 h-3.5 text-[#007AFF] shrink-0" />
              <span>{{ group.label }}</span>
              <span class="text-[#C7C7CC] font-normal">({{ group.producers.length }})</span>
            </div>

            <ul class="rounded-2xl border border-[#E5E5E5] divide-y divide-[#E5E5E5] overflow-hidden">
              <li
                v-for="producer in group.producers"
                :key="producer.id"
                class="px-4 py-3 flex items-start justify-between gap-3 bg-white"
              >
                <div class="min-w-0 flex gap-2.5">
                  <AlertTriangle class="w-4 h-4 text-[#FF3B30] mt-0.5 shrink-0" />
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-[#1D1D1F] truncate">{{ producer.full_name }}</p>
                    <p class="text-xs text-[#8E8E93] truncate">
                      {{ producer.code || 'Sin código' }}
                      <span v-if="producer.identity_number"> · {{ producer.identity_number }}</span>
                    </p>
                  </div>
                </div>
                <div class="text-right shrink-0">
                  <p class="text-sm font-semibold text-[#FF3B30] tabular-nums">{{ money(producer.price) }}/L</p>
                  <p class="text-[11px] text-[#8E8E93]">base {{ money(basePrice) }}</p>
                </div>
              </li>
            </ul>
          </section>
        </div>

        <div class="p-4 border-t border-[#E5E5E5] shrink-0 flex justify-end">
          <button
            type="button"
            @click="close"
            class="px-4 py-2.5 border border-[#E5E5E5] rounded-xl text-sm font-medium text-[#1D1D1F] hover:bg-[#F5F5F7]"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
