<script setup>
import AppShell from '../../../Components/AppShell.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { ArrowLeft, User, MapPin, Phone, Building, FileText, Check, CreditCard, Truck } from '@lucide/vue'

const props = defineProps({
  routes: Array
})

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
  form.post('/producers')
}
</script>

<template>
  <AppShell>
    <div class="mb-6">
      <Link href="/producers" class="inline-flex items-center text-[#007AFF] hover:text-[#0056CC] mb-4">
        <ArrowLeft class="w-4 h-4 mr-2" />
        Volver a productores
      </Link>
      <h1 class="text-3xl font-display font-bold text-[#1D1D1F]">Nuevo Productor</h1>
      <p class="text-[#6E6E73] mt-1">Registra un nuevo productor de leche</p>
    </div>

    <div v-if="$page.props.flash.error" class="bg-[#FFE5E5] border border-[#FF3B30] text-[#FF3B30] px-4 py-3 rounded-xl mb-6">
      {{ $page.props.flash.error }}
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-2xl border border-[#E5E5E5] p-6 shadow-sm">
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

      <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-[#E5E5E5]">
        <Link 
          href="/producers" 
          class="px-4 py-2 border border-[#E5E5E5] rounded-xl hover:bg-[#F5F5F7] text-[#1D1D1F] text-sm font-medium transition-colors"
        >
          Cancelar
        </Link>
        <button 
          type="submit" 
          :disabled="form.processing" 
          class="inline-flex items-center px-4 py-2 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-50 transition-colors text-sm font-medium shadow-sm"
        >
          <Check v-if="!form.processing" class="w-4 h-4 mr-2" />
          {{ form.processing ? 'Guardando...' : 'Crear productor' }}
        </button>
      </div>
    </form>
  </AppShell>
</template>
