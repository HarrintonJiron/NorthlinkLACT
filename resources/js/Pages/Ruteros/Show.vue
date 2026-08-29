<script setup>
import { useForm, Link, router } from '@inertiajs/vue3'
import AppShell from '../../Components/AppShell.vue'
import StatusBadge from '../../Components/StatusBadge.vue'
import { ArrowLeft, User, Phone, CreditCard, Truck, MapPin, Check, Power } from '@lucide/vue'

const props = defineProps({
  rutero: Object,
  availableRoutes: {
    type: Array,
    default: () => [],
  },
})

const form = useForm({
  full_name: props.rutero.full_name || '',
  identity_number: props.rutero.identity_number || '',
  phone: props.rutero.phone || '',
  vehicle_plate: props.rutero.vehicle_plate || '',
  route_id: props.rutero.route_id || '',
})

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
  if (!/^\d{3}-\d{5}-\d{4}[A-Za-z]?$/.test(form.identity_number)) {
    form.errors.identity_number = 'El formato de la cédula debe ser XXX-XXXXX-XXXX (ejemplo: 001-12345-0001A)'
    return
  }
  form.put(`/ruteros/${props.rutero.id}`)
}

const toggleActive = () => {
  const action = props.rutero.active ? 'desactivar' : 'activar'
  if (!confirm(`¿Seguro que quieres ${action} a ${props.rutero.full_name}?`)) {
    return
  }
  router.patch(`/ruteros/${props.rutero.id}/toggle`)
}
</script>

<template>
  <AppShell>
    <div class="mb-8">
      <Link href="/ruteros" class="inline-flex items-center text-[#007AFF] hover:text-[#0056CC] mb-4">
        <ArrowLeft class="w-4 h-4 mr-2" />
        Volver a ruteros
      </Link>
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-3xl font-display font-bold text-[#1D1D1F]">{{ rutero.full_name }}</h1>
            <StatusBadge
              :status="rutero.active ? 'completed' : 'cancelled'"
              :label="rutero.active ? 'Activo' : 'Inactivo'"
            />
          </div>
          <p class="text-[#6E6E73] mt-1">Datos del propietario y la ruta que recorre</p>
        </div>
        <button
          type="button"
          @click="toggleActive"
          :class="[
            'inline-flex items-center px-5 py-2.5 rounded-xl font-medium shadow-sm',
            rutero.active
              ? 'bg-[#FFE5E5] text-[#FF3B30] hover:bg-[#FFD1D1]'
              : 'bg-[#E8F8E8] text-[#34C759] hover:bg-[#D4F4D4]',
          ]"
        >
          <Power class="w-4 h-4 mr-2" />
          {{ rutero.active ? 'Desactivar' : 'Activar' }}
        </button>
      </div>
    </div>

    <div
      v-if="$page.props.flash?.success"
      class="bg-[#E8F8E8] border border-[#34C759] text-[#1D7A32] px-4 py-3 rounded-xl mb-6"
    >
      {{ $page.props.flash.success }}
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-2xl border border-[#E5E5E5] p-6 shadow-sm">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <h3 class="text-base font-semibold text-[#1D1D1F] mb-4 flex items-center">
            <User class="w-4 h-4 mr-2 text-[#007AFF]" />
            Propietario de la ruta
          </h3>
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Nombre *</label>
          <input
            type="text"
            v-model="form.full_name"
            required
            class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
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
              maxlength="15"
              required
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
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
              required
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
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
              required
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] uppercase"
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
              <option v-for="route in availableRoutes" :key="route.id" :value="route.id">
                {{ route.code }} — {{ route.name }}
              </option>
            </select>
          </div>
          <p v-if="form.errors.route_id" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.route_id }}</p>
        </div>
      </div>

      <div class="flex items-center justify-end mt-6 pt-4 border-t border-[#E5E5E5]">
        <button
          type="submit"
          :disabled="form.processing"
          class="inline-flex items-center px-5 py-2.5 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-50 font-medium shadow-sm"
        >
          <Check v-if="!form.processing" class="w-4 h-4 mr-2" />
          {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
        </button>
      </div>
    </form>
  </AppShell>
</template>
