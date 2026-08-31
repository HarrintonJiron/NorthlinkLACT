<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { User, Phone, CreditCard, Truck, Check, X, UserCog } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  rutero: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'submit'])

const isEditing = computed(() => Boolean(props.rutero?.id))

const form = useForm({
  owner_name: '',
  owner_identity_number: '',
  owner_phone: '',
  vehicle_description: '',
  vehicle_plate: '',
  driver_name: '',
  driver_identity_number: '',
  driver_phone: '',
})

const resetForm = () => {
  if (props.rutero) {
    form.owner_name = props.rutero.owner_name || ''
    form.owner_identity_number = props.rutero.owner_identity_number || ''
    form.owner_phone = props.rutero.owner_phone || ''
    form.vehicle_description = props.rutero.vehicle_description || ''
    form.vehicle_plate = props.rutero.vehicle_plate || ''
    form.driver_name = props.rutero.driver_name || ''
    form.driver_identity_number = props.rutero.driver_identity_number || ''
    form.driver_phone = props.rutero.driver_phone || ''
  } else {
    form.reset()
  }
  form.clearErrors()
}

watch(() => props.show, (visible) => {
  if (visible) {
    resetForm()
  }
})

watch(() => props.rutero, () => {
  if (props.show) {
    resetForm()
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

const handleOwnerIdentityInput = (e) => {
  form.owner_identity_number = formatIdentityNumber(e.target.value)
}

const handleDriverIdentityInput = (e) => {
  form.driver_identity_number = formatIdentityNumber(e.target.value)
}

const submit = () => {
  if (!validateIdentityNumber(form.owner_identity_number)) {
    form.errors.owner_identity_number = 'El formato de la cédula debe ser XXX-XXXXX-XXXX (ejemplo: 001-12345-0001A)'
    return
  }
  if (!validateIdentityNumber(form.driver_identity_number)) {
    form.errors.driver_identity_number = 'El formato de la cédula debe ser XXX-XXXXX-XXXX (ejemplo: 001-12345-0001A)'
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
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
      <div class="flex items-center justify-between p-6 border-b border-[#E5E5E5]">
        <h2 class="text-xl font-display font-bold text-[#1D1D1F]">
          {{ isEditing ? 'Editar rutero' : 'Nuevo rutero' }}
        </h2>
        <button type="button" @click="close" class="p-2 rounded-lg hover:bg-[#F5F5F7] text-[#8E8E93]">
          <X class="w-5 h-5" />
        </button>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <h3 class="text-base font-semibold text-[#1D1D1F] flex items-center">
              <User class="w-4 h-4 mr-2 text-[#007AFF]" />
              Propietario del camión
            </h3>
            <p class="text-sm text-[#8E8E93] mt-1">Dueño del vehículo que opera la ruta.</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Nombre *</label>
            <input
              type="text"
              v-model="form.owner_name"
              placeholder="Nombre del propietario"
              required
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
            <p v-if="form.errors.owner_name" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.owner_name }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Cédula *</label>
            <div class="relative">
              <CreditCard class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input
                type="text"
                v-model="form.owner_identity_number"
                @input="handleOwnerIdentityInput"
                placeholder="001-12345-0001A"
                maxlength="15"
                required
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
              />
            </div>
            <p v-if="form.errors.owner_identity_number" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.owner_identity_number }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Celular *</label>
            <div class="relative">
              <Phone class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input
                type="text"
                v-model="form.owner_phone"
                placeholder="8888-0000"
                required
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
              />
            </div>
            <p v-if="form.errors.owner_phone" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.owner_phone }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Vehículo *</label>
            <div class="relative">
              <Truck class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input
                type="text"
                v-model="form.vehicle_description"
                placeholder="Ej: Isuzu NPR"
                required
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
              />
            </div>
            <p v-if="form.errors.vehicle_description" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.vehicle_description }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Placa *</label>
            <input
              type="text"
              v-model="form.vehicle_plate"
              placeholder="M-1010"
              required
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93] uppercase"
            />
            <p v-if="form.errors.vehicle_plate" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.vehicle_plate }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-[#E5E5E5]">
          <div class="md:col-span-2">
            <h3 class="text-base font-semibold text-[#1D1D1F] flex items-center">
              <UserCog class="w-4 h-4 mr-2 text-[#007AFF]" />
              Encargado de la ruta
            </h3>
            <p class="text-sm text-[#8E8E93] mt-1">Persona que recorre y opera la ruta día a día.</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Nombre *</label>
            <input
              type="text"
              v-model="form.driver_name"
              placeholder="Nombre del encargado"
              required
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
            <p v-if="form.errors.driver_name" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.driver_name }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Cédula *</label>
            <div class="relative">
              <CreditCard class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input
                type="text"
                v-model="form.driver_identity_number"
                @input="handleDriverIdentityInput"
                placeholder="001-12345-0001A"
                maxlength="15"
                required
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
              />
            </div>
            <p v-if="form.errors.driver_identity_number" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.driver_identity_number }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Celular *</label>
            <div class="relative">
              <Phone class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input
                type="text"
                v-model="form.driver_phone"
                placeholder="8777-0000"
                required
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
              />
            </div>
            <p v-if="form.errors.driver_phone" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.driver_phone }}</p>
          </div>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-[#E5E5E5]">
          <button type="button" @click="close" class="px-4 py-2 border border-[#E5E5E5] rounded-xl hover:bg-[#F5F5F7] text-[#1D1D1F] text-sm font-medium">
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-4 py-2 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-50 text-sm font-medium shadow-sm"
          >
            <Check v-if="!form.processing" class="w-4 h-4 mr-2" />
            {{ form.processing ? 'Guardando...' : (isEditing ? 'Guardar cambios' : 'Guardar rutero') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
