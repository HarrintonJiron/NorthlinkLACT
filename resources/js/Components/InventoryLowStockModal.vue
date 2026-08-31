<script setup>
import { computed } from 'vue'
import { X, AlertTriangle, Ruler } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  products: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close'])

const formatQty = (value) =>
  Number(value || 0).toLocaleString('es-NI', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 3,
  })

const grouped = computed(() => {
  const map = new Map()

  for (const product of props.products) {
    const key = product.unit?.id ? String(product.unit.id) : 'none'
    const label = product.unit
      ? `${product.unit.name} (${product.unit.symbol || product.unit.code})`
      : 'Sin unidad'

    if (!map.has(key)) {
      map.set(key, { key, label, products: [] })
    }
    map.get(key).products.push(product)
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
            <p class="text-[11px] tracking-[0.18em] uppercase text-[#FF3B30]">Inventario</p>
            <h2 class="text-lg font-display font-bold text-[#1D1D1F]">Productos con stock bajo</h2>
            <p class="text-xs text-[#8E8E93] mt-0.5">
              {{ products.length }} producto{{ products.length === 1 ? '' : 's' }} en o bajo el mínimo
            </p>
          </div>
          <button type="button" @click="close" class="p-2 rounded-lg hover:bg-[#F5F5F7] text-[#8E8E93] shrink-0">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="overflow-y-auto p-5 space-y-4">
          <div
            v-if="!products.length"
            class="rounded-2xl bg-[#F5F5F7] px-4 py-8 text-center text-sm text-[#8E8E93]"
          >
            No hay productos con stock bajo.
          </div>

          <section v-for="group in grouped" :key="group.key" class="space-y-2">
            <div class="flex items-center gap-2 text-xs font-semibold text-[#6E6E73]">
              <Ruler class="w-3.5 h-3.5 text-[#007AFF] shrink-0" />
              <span>{{ group.label }}</span>
              <span class="text-[#C7C7CC] font-normal">({{ group.products.length }})</span>
            </div>

            <ul class="rounded-2xl border border-[#E5E5E5] divide-y divide-[#E5E5E5] overflow-hidden">
              <li
                v-for="product in group.products"
                :key="product.id"
                class="px-4 py-3 flex items-start justify-between gap-3 bg-white"
              >
                <div class="min-w-0 flex gap-2.5">
                  <AlertTriangle class="w-4 h-4 text-[#FF3B30] mt-0.5 shrink-0" />
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-[#1D1D1F] truncate">{{ product.name }}</p>
                    <p class="text-xs text-[#8E8E93] truncate">{{ product.code }}</p>
                  </div>
                </div>
                <div class="text-right shrink-0">
                  <p class="text-sm font-semibold text-[#FF3B30] tabular-nums">
                    {{ formatQty(product.stock) }}
                  </p>
                  <p class="text-[11px] text-[#8E8E93]">mín {{ formatQty(product.min_stock) }}</p>
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
