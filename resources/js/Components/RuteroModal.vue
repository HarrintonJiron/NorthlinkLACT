<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { User, Phone, CreditCard, Truck, MapPin, Check, X } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  routes: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close', 'submit'])

const form = useForm({
  full_name: '',
  identity_number: '',
  phone: '',
  vehicle_plate: '',
  route_id: '',
})

watch(() => props.show, (visible) => {
  if (visible) {
    form.reset()
    form.clearErrors()
  }
})

const validateIdentityNumber = (value) => /^\d{3}-\d{5}-\d{4}[A-Za-z]?$/.test(value)

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
  if (!form.route_id) {
    form.errors.route_id = 'El rutero debe tener una ruta asignada.'
    return
  }
  if (!validateIdentityNumber(form.identity_number)) {
    form.errors.identity_number = 'El formato de la cédula debe ser XXX-XXXXX-XXXX (ejemplo: 001-12345-0001A)'
    return
  }
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
    <div class="bg-white rounded-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl">
      <div class="flex items-center justify-between p-6 border-b border-[#E5E5E5]">
        <h2 class="text-xl font-display font-bold text-[#1D1D1F]">Nuevo rutero</h2>
        <button type="button" @click="close" class="p-2 rounded-lg hover:bg-[#F5F5F7] text-[#8E8E93]">
          <X class="w-5 h-5" />
        </button>
      </div>

      <form @submit.prevent="submit" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <h3 class="text-base font-semibold text-[#1D1D1F] mb-1 flex items-center">
              <User class="w-4 h-4 mr-2 text-[#007AFF]" />
              Propietario de la ruta
            </h3>
            <p class="text-sm text-[#8E8E93] mb-3">Datos de quien recorre el trayecto.</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Nombre *</label>
            <input
              type="text"
              v-model="form.full_name"
              placeholder="Nombre del propietario"
              required
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
            <p v-if="form.errors.full_name" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.full_name }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Cédula *</label>
            <div class="relative">
              <CreditCard class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input
                type="text"
                v-model="form.identity_number"
                @input="handleIdentityInput"
                placeholder="001-12345-0001A"
                maxlength="15"
                required
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
              />
            </div>
            <p v-if="form.errors.identity_number" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.identity_number }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Número de teléfono *</label>
            <div class="relative">
              <Phone class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input
                type="text"
                v-model="form.phone"
                placeholder="+505 0000 0000"
                required
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
              />
            </div>
            <p v-if="form.errors.phone" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.phone }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Placa del vehículo *</label>
            <div class="relative">
              <Truck class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input
                type="text"
                v-model="form.vehicle_plate"
                placeholder="M 123456"
                required
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93] uppercase"
              />
            </div>
            <p v-if="form.errors.vehicle_plate" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.vehicle_plate }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Ruta asignada *</label>
            <div class="relative">
              <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <select
                v-model="form.route_id"
                required
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
              >
                <option value="">Selecciona una ruta</option>
                <option v-for="route in routes" :key="route.id" :value="route.id">
                  {{ route.code }} — {{ route.name }}
                </option>
              </select>
            </div>
            <p v-if="form.errors.route_id" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.route_id }}</p>
          </div>
        </div>

        <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-[#E5E5E5]">
          <button type="button" @click="close" class="px-4 py-2 border border-[#E5E5E5] rounded-xl hover:bg-[#F5F5F7] text-[#1D1D1F] text-sm font-medium">
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-4 py-2 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-50 text-sm font-medium shadow-sm"
          >
            <Check v-if="!form.processing" class="w-4 h-4 mr-2" />
            {{ form.processing ? 'Guardando...' : 'Guardar rutero' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
