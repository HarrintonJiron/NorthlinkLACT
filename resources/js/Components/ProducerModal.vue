<script setup>
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { User, MapPin, Phone, Building, Check, CreditCard, Truck, X } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  routes: Array,
  producer: Object
})

const emit = defineEmits(['close', 'submit'])

const form = useForm({
  code: '',
  full_name: '',
  identity_number: '',
  phone: '',
  address: '',
  community: '',
  municipality: '',
  department: '',
  latitude: '',
  longitude: '',
  active: true,
  route_id: '',
  payment_method: 'cash',
})

watch(() => props.producer, (newProducer) => {
  if (newProducer) {
    form.code = newProducer.code || ''
    form.full_name = newProducer.full_name || ''
    form.identity_number = newProducer.identity_number || ''
    form.phone = newProducer.phone || ''
    form.address = newProducer.address || ''
    form.community = newProducer.community || ''
    form.municipality = newProducer.municipality || ''
    form.department = newProducer.department || ''
    form.latitude = newProducer.latitude || ''
    form.longitude = newProducer.longitude || ''
    form.active = newProducer.active ?? true
    form.route_id = newProducer.active_assignment?.route_id || ''
    form.payment_method = newProducer.active_assignment?.payment_method || 'cash'
  }
})

const validateIdentityNumber = (value) => {
  if (!value) return true
  const regex = /^\d{3}-\d{5}-\d{4}[A-Za-z]?$/
  return regex.test(value)
}

const formatIdentityNumber = (value) => {
  const cleaned = value.replace(/\D/g, '')
  if (cleaned.length <= 3) return cleaned
  if (cleaned.length <= 8) return `${cleaned.slice(0, 3)}-${cleaned.slice(3)}`
  if (cleaned.length <= 12) return `${cleaned.slice(0, 3)}-${cleaned.slice(3, 8)}-${cleaned.slice(8)}`
  return `${cleaned.slice(0, 3)}-${cleaned.slice(3, 8)}-${cleaned.slice(8, 12)}${cleaned.slice(12) || ''}`
}

const handleIdentityInput = (e) => {
  const formatted = formatIdentityNumber(e.target.value)
  form.identity_number = formatted
}

const submit = () => {
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
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-[#E5E5E5]">
        <h2 class="text-xl font-display font-bold text-[#1D1D1F]">
          {{ producer ? 'Editar Productor' : 'Nuevo Productor' }}
        </h2>
        <button 
          @click="close"
          class="p-2 rounded-lg hover:bg-[#F5F5F7] transition-colors text-[#8E8E93]"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Información Personal -->
          <div class="md:col-span-2">
            <h3 class="text-base font-semibold text-[#1D1D1F] mb-4 flex items-center">
              <User class="w-4 h-4 mr-2 text-[#007AFF]" />
              Información Personal
            </h3>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Código</label>
            <input 
              type="text" 
              v-model="form.code" 
              placeholder="Opcional"
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Nombre Completo *</label>
            <input 
              type="text" 
              v-model="form.full_name" 
              placeholder="Nombre del productor"
              required
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Cédula (Formato: XXX-XXXXX-XXXX)</label>
            <input 
              type="text" 
              v-model="form.identity_number"
              @input="handleIdentityInput"
              placeholder="001-12345-0001A"
              maxlength="15"
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
            <p v-if="form.errors.identity_number" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.identity_number }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Teléfono</label>
            <div class="relative">
              <Phone class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <input 
                type="text" 
                v-model="form.phone" 
                placeholder="+505 0000 0000"
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
              />
            </div>
          </div>

          <!-- Ubicación -->
          <div class="md:col-span-2 mt-2">
            <h3 class="text-base font-semibold text-[#1D1D1F] mb-4 flex items-center">
              <MapPin class="w-4 h-4 mr-2 text-[#007AFF]" />
              Ubicación
            </h3>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Dirección</label>
            <input 
              type="text" 
              v-model="form.address" 
              placeholder="Dirección completa"
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Comunidad</label>
            <input 
              type="text" 
              v-model="form.community" 
              placeholder="Comunidad"
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Municipio</label>
            <input 
              type="text" 
              v-model="form.municipality" 
              placeholder="Municipio"
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Departamento</label>
            <input 
              type="text" 
              v-model="form.department" 
              placeholder="Departamento"
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Latitud</label>
            <input 
              type="number" 
              step="0.00000001" 
              v-model="form.latitude" 
              placeholder="12.865432"
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Longitud</label>
            <input 
              type="number" 
              step="0.00000001" 
              v-model="form.longitude" 
              placeholder="-85.123456"
              class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>

          <!-- Asignación -->
          <div class="md:col-span-2 mt-2">
            <h3 class="text-base font-semibold text-[#1D1D1F] mb-4 flex items-center">
              <Truck class="w-4 h-4 mr-2 text-[#007AFF]" />
              Asignación
            </h3>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Ruta</label>
            <div class="relative">
              <MapPin class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <select 
                v-model="form.route_id"
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
              >
                <option value="">Seleccionar ruta</option>
                <option v-for="route in routes" :key="route.id" :value="route.id">
                  {{ route.name }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Forma de Pago</label>
            <div class="relative">
              <CreditCard class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
              <select 
                v-model="form.payment_method"
                class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
              >
                <option value="cash">Efectivo</option>
                <option value="transfer">Transferencia</option>
                <option value="check">Cheque</option>
              </select>
            </div>
          </div>

          <!-- Estado -->
          <div class="md:col-span-2 mt-2">
            <label class="flex items-center space-x-3 cursor-pointer">
              <input 
                type="checkbox" 
                v-model="form.active"
                class="w-4 h-4 text-[#007AFF] rounded focus:ring-[#007AFF]/50"
              />
              <span class="text-sm text-[#1D1D1F]">Productor activo</span>
            </label>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-[#E5E5E5]">
          <button 
            type="button"
            @click="close"
            class="px-4 py-2 border border-[#E5E5E5] rounded-xl hover:bg-[#F5F5F7] text-[#1D1D1F] text-sm font-medium transition-colors"
          >
            Cancelar
          </button>
          <button 
            type="submit" 
            :disabled="form.processing" 
            class="inline-flex items-center px-4 py-2 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-50 transition-colors text-sm font-medium shadow-sm"
          >
            <Check v-if="!form.processing" class="w-4 h-4 mr-2" />
            {{ form.processing ? 'Guardando...' : (producer ? 'Actualizar' : 'Crear') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
