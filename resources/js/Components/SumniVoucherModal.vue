<script setup>
import { computed } from 'vue'
import { Printer, X } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  voucher: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close'])

const densityLabel = computed(() => {
  const value = props.voucher?.density
  if (value == null) return 'Sin registrar'
  if (value < 25) return `${value} °C · posible agua`
  if (value > 25) return `${value} °C · tiende a cortarse`
  return `${value} °C · buena leche`
})

const close = () => emit('close')

const printVoucher = () => {
  window.print()
}
</script>

<template>
  <div
    v-if="show && voucher"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 print:static print:bg-transparent print:p-0 print:inset-auto"
    @click.self="close"
  >
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden print:shadow-none print:rounded-none print:max-w-none">
      <div class="flex items-center justify-between p-4 border-b border-[#E5E5E5] print:hidden">
        <h2 class="text-lg font-display font-bold text-[#1D1D1F]">Baucher de entrega</h2>
        <button type="button" @click="close" class="p-2 rounded-lg hover:bg-[#F5F5F7] text-[#8E8E93]">
          <X class="w-5 h-5" />
        </button>
      </div>

      <div id="sumni-voucher" class="p-6 text-[#1D1D1F]">
        <div class="text-center border-b border-dashed border-[#C7C7CC] pb-4 mb-4">
          <p class="text-[11px] tracking-[0.2em] uppercase text-[#007AFF] print:text-black">Northlink LACT</p>
          <h3 class="text-xl font-display font-bold mt-1">Comprobante de acopio</h3>
          <p class="text-sm text-[#8E8E93] print:text-black mt-1">Entrega del día</p>
        </div>

        <dl class="space-y-3 text-sm">
          <div class="flex justify-between gap-3">
            <dt class="text-[#8E8E93] print:text-black">Fecha</dt>
            <dd class="font-semibold text-right">{{ voucher.date }}</dd>
          </div>
          <div class="flex justify-between gap-3">
            <dt class="text-[#8E8E93] print:text-black">Productor</dt>
            <dd class="font-semibold text-right">{{ voucher.producer_name }}</dd>
          </div>
          <div v-if="voucher.producer_code" class="flex justify-between gap-3">
            <dt class="text-[#8E8E93] print:text-black">Código</dt>
            <dd class="text-right">{{ voucher.producer_code }}</dd>
          </div>
          <div v-if="voucher.identity_number" class="flex justify-between gap-3">
            <dt class="text-[#8E8E93] print:text-black">Cédula</dt>
            <dd class="text-right">{{ voucher.identity_number }}</dd>
          </div>
          <div class="flex justify-between gap-3">
            <dt class="text-[#8E8E93] print:text-black">Ruta</dt>
            <dd class="text-right">{{ voucher.route_code }} — {{ voucher.route_name }}</dd>
          </div>
          <div v-if="voucher.driver_name" class="flex justify-between gap-3">
            <dt class="text-[#8E8E93] print:text-black">Encargado</dt>
            <dd class="text-right">{{ voucher.driver_name }}</dd>
          </div>
        </dl>

        <div class="mt-5 rounded-2xl bg-[#F5F5F7] print:bg-transparent print:border print:border-black p-4">
          <div class="flex justify-between items-end gap-3">
            <div>
              <p class="text-xs text-[#8E8E93] print:text-black">Litros entregados</p>
              <p class="text-3xl font-display font-bold">{{ voucher.liters }} L</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-[#8E8E93] print:text-black">Densidad</p>
              <p class="text-base font-semibold">{{ densityLabel }}</p>
            </div>
          </div>
        </div>

        <div class="mt-5 pt-4 border-t border-dashed border-[#C7C7CC] print:border-black">
          <p class="text-xs text-[#6E6E73] print:text-black text-center leading-relaxed">
            Conserva este comprobante como constancia de la entrega del día.
            Cualquier disconformidad con litros o densidad debe reclamarse en el momento de la entrega.
          </p>
        </div>
      </div>

      <div class="p-4 border-t border-[#E5E5E5] flex gap-2 print:hidden">
        <button
          type="button"
          @click="close"
          class="flex-1 px-4 py-2.5 border border-[#E5E5E5] rounded-xl text-sm font-medium text-[#1D1D1F] hover:bg-[#F5F5F7]"
        >
          Cerrar
        </button>
        <button
          type="button"
          @click="printVoucher"
          class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] text-sm font-semibold"
        >
          <Printer class="w-4 h-4 mr-2" />
          Imprimir baucher
        </button>
      </div>
    </div>
  </div>
</template>

<style>
@media print {
  body * {
    visibility: hidden !important;
  }

  #sumni-voucher,
  #sumni-voucher * {
    visibility: visible !important;
  }

  #sumni-voucher {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    padding: 16px;
  }
}
</style>
