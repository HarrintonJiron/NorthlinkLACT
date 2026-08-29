<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { MapPin, Hash, Check, X } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  nextCode: {
    type: String,
    default: 'RUT-0001',
  },
})

const emit = defineEmits(['close', 'submit'])

const form = useForm({
  name: '',
})

watch(() => props.show, (visible) => {
  if (visible) {
    form.reset()
    form.clearErrors()
  }
})

const submit = () => {
  emit('submit', form)
}

const close = () => {
  emit('close')
  form.reset()
  form.clearErrors()
}
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    @click.self="close"
  >
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl">
      <div class="flex items-center justify-between p-6 border-b border-[#E5E5E5]">
        <h2 class="text-xl font-display font-bold text-[#1D1D1F]">Nueva ruta</h2>
        <button
          type="button"
          @click="close"
          class="p-2 rounded-lg hover:bg-[#F5F5F7] transition-colors text-[#8E8E93]"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-4">
        <p class="text-sm text-[#8E8E93]">
          Solo el trayecto. El propietario se registra después en Rutero.
        </p>

        <div>
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">ID de ruta</label>
          <div class="relative">
            <Hash class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="text"
              :value="nextCode"
              disabled
              class="pl-10 w-full bg-[#EFEFF4] border-none rounded-xl py-2.5 px-3 text-sm text-[#6E6E73] cursor-not-allowed"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Nombre de la ruta *</label>
          <div class="relative">
            <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="text"
              v-model="form.name"
              placeholder="Ej: Ruta Matagalpa Norte"
              required
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
          <p v-if="form.errors.name" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.name }}</p>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-[#E5E5E5]">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 border border-[#E5E5E5] rounded-xl hover:bg-[#F5F5F7] text-[#1D1D1F] text-sm font-medium"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-4 py-2 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-50 text-sm font-medium shadow-sm"
          >
            <Check v-if="!form.processing" class="w-4 h-4 mr-2" />
            {{ form.processing ? 'Guardando...' : 'Guardar ruta' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
