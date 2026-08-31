<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { User, Phone, CreditCard, Check, X } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  routeId: {
    type: [Number, String],
    required: true,
  },
})

const emit = defineEmits(['close'])

const form = useForm({
  full_name: '',
  identity_number: '',
  phone: '',
})

watch(() => props.show, (visible) => {
  if (visible) {
    form.reset()
    form.clearErrors()
  }
})

const validateIdentityNumber = (value) => !value || /^\d{3}-\d{5}-\d{4}[A-Za-z]?$/.test(value)

const formatIdentityNumber = (value) => {
  const cleaned = value.replace(/\D/g, '')
  if (cleaned.length <= 3) return cleaned
  if (cleaned.length <= 8) return `${cleaned.slice(0, 3)}-${cleaned.slice(3)}`
  if (cleaned.length <= 12) return `${cleaned.slice(0, 3)}-${cleaned.slice(3, 8)}-${cleaned.slice(8)}`
  return `${cleaned.slice(0, 3)}-${cleaned.slice(3, 8)}-${cleaned.slice(8, 12)}`
}

const handleIdentityInput = (e) => {
  form.identity_number = formatIdentityNumber(e.target.value)
}

const submit = () => {
  if (form.identity_number && !validateIdentityNumber(form.identity_number)) {
    form.errors.identity_number = 'El formato de la cédula debe ser XXX-XXXXX-XXXX (ejemplo: 001-12345-0001A)'
    return
  }

  form.post(`/sumni/${props.routeId}/producers`, {
    preserveScroll: true,
    onSuccess: () => emit('close'),
  })
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
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
      <div class="flex items-center justify-between p-6 border-b border-[#E5E5E5]">
        <h2 class="text-xl font-display font-bold text-[#1D1D1F]">Nuevo cliente</h2>
        <button type="button" @click="close" class="p-2 rounded-lg hover:bg-[#F5F5F7] text-[#8E8E93]">
          <X class="w-5 h-5" />
        </button>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-4">
        <p class="text-sm text-[#8E8E93]">
          Se registrará en esta ruta y aparecerá en Productores como los demás clientes.
        </p>

        <div>
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Nombre completo *</label>
          <div class="relative">
            <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="text"
              v-model="form.full_name"
              required
              autofocus
              placeholder="Nombre del productor"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
            />
          </div>
          <p v-if="form.errors.full_name" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.full_name }}</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Cédula <span class="text-[#8E8E93] font-normal">(opcional)</span></label>
          <div class="relative">
            <CreditCard class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="text"
              v-model="form.identity_number"
              @input="handleIdentityInput"
              maxlength="15"
              placeholder="001-12345-0001A"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
            />
          </div>
          <p class="text-xs text-[#8E8E93] mt-1">Puedes dejarla vacía si no la tienes a mano.</p>
          <p v-if="form.errors.identity_number" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.identity_number }}</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Teléfono *</label>
          <div class="relative">
            <Phone class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="text"
              v-model="form.phone"
              required
              placeholder="8888-0000"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
            />
          </div>
          <p v-if="form.errors.phone" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.phone }}</p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button type="button" @click="close" class="px-4 py-2 text-sm font-medium text-[#6E6E73] hover:text-[#1D1D1F]">
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-4 py-2 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-50 text-sm font-medium"
          >
            <Check v-if="!form.processing" class="w-4 h-4 mr-2" />
            {{ form.processing ? 'Guardando...' : 'Registrar cliente' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
