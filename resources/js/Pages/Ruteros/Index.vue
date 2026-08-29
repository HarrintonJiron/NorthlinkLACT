<script setup>
import { computed, ref } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import RuteroModal from '../../Components/RuteroModal.vue'
import { Link } from '@inertiajs/vue3'
import { Truck, Plus, Search, User, Phone, MapPin } from '@lucide/vue'

const props = defineProps({
  ruteros: {
    type: Array,
    default: () => [],
  },
  availableRoutes: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({ total: 0, active: 0, inactive: 0 }),
  },
})

const showCreateModal = ref(false)
const searchQuery = ref('')

const filteredRuteros = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const list = props.ruteros || []
  if (!query) return list

  return list.filter((rutero) =>
    [
      rutero.full_name,
      rutero.identity_number,
      rutero.phone,
      rutero.vehicle_plate,
      rutero.route?.code,
      rutero.route?.name,
    ]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(query))
  )
})

const handleCreate = (form) => {
  form.post('/ruteros', {
    onSuccess: () => {
      showCreateModal.value = false
    },
  })
}
</script>

<template>
  <AppShell>
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-[11px] tracking-[0.22em] uppercase text-[#007AFF]">Acopio</p>
        <h1 class="text-4xl font-display font-bold text-[#1D1D1F] mt-1">Ruteros</h1>
        <p class="text-sm text-[#8E8E93] mt-1">
          Propietarios de cada trayecto · {{ stats.total }} registrados
        </p>
      </div>
      <button
        type="button"
        @click="showCreateModal = true"
        class="inline-flex items-center px-5 py-2.5 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] text-sm font-medium shadow-sm"
      >
        <Plus class="w-4 h-4 mr-2" />
        Nuevo rutero
      </button>
    </div>

    <div
      v-if="$page.props.flash?.success"
      class="bg-[#E8F8E8] border border-[#34C759] text-[#1D7A32] px-4 py-3 rounded-xl mb-6"
    >
      {{ $page.props.flash.success }}
    </div>

    <div class="rounded-[28px] border border-[#E5E5E5] bg-white overflow-hidden shadow-sm">
      <div class="p-4 border-b border-[#E5E5E5] flex items-center justify-between gap-3">
        <div class="relative flex-1 sm:flex-none">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Buscar rutero, cédula o ruta..."
            class="pl-10 pr-4 py-2 bg-[#F5F5F7] border-none rounded-full focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-sm w-full sm:w-72 text-[#1D1D1F] placeholder-[#8E8E93]"
          />
        </div>
        <p class="text-sm text-[#8E8E93]">{{ filteredRuteros.length }} ruteros</p>
      </div>

      <div v-if="filteredRuteros.length" class="divide-y divide-[#E5E5E5]">
        <div v-for="rutero in filteredRuteros" :key="rutero.id" class="p-4 hover:bg-[#F5F5F7]">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center space-x-4">
              <div class="bg-[#E5F1FF] p-3 rounded-xl">
                <Truck class="w-5 h-5 text-[#007AFF]" />
              </div>
              <div>
                <Link :href="`/ruteros/${rutero.id}`" class="font-medium text-[#1D1D1F] hover:text-[#007AFF]">
                  {{ rutero.full_name }}
                </Link>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-[#8E8E93] mt-1">
                  <span class="inline-flex items-center">
                    <User class="w-3.5 h-3.5 mr-1" />
                    {{ rutero.identity_number }}
                  </span>
                  <span class="inline-flex items-center">
                    <Phone class="w-3.5 h-3.5 mr-1" />
                    {{ rutero.phone }}
                  </span>
                  <span class="inline-flex items-center">
                    <MapPin class="w-3.5 h-3.5 mr-1" />
                    {{ rutero.route ? `${rutero.route.code} — ${rutero.route.name}` : 'Sin ruta' }}
                  </span>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-3 pl-14 sm:pl-0">
              <span
                :class="rutero.active ? 'bg-[#E8F8E8] text-[#34C759]' : 'bg-[#F5F5F7] text-[#8E8E93]'"
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
              >
                {{ rutero.active ? 'Activo' : 'Inactivo' }}
              </span>
              <Link :href="`/ruteros/${rutero.id}`" class="text-[#007AFF] hover:text-[#0056CC] font-medium text-sm">
                Ver detalles →
              </Link>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-12">
        <Truck class="w-12 h-12 text-[#007AFF]/40 mx-auto mb-3" />
        <p class="text-[#1D1D1F] font-medium mb-1">{{ searchQuery ? 'Sin resultados' : 'No hay ruteros' }}</p>
        <p class="text-sm text-[#8E8E93] mb-4">
          {{ searchQuery ? 'No encontramos ruteros que coincidan.' : 'Primero crea el trayecto en Rutas y luego registra al propietario.' }}
        </p>
        <button
          v-if="!searchQuery"
          type="button"
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-[#007AFF] text-[#007AFF] rounded-xl hover:bg-[#E5F1FF] text-sm font-medium"
        >
          <Plus class="w-4 h-4 mr-2" />
          Registrar primer rutero
        </button>
      </div>
    </div>

    <RuteroModal
      :show="showCreateModal"
      :routes="availableRoutes"
      @close="showCreateModal = false"
      @submit="handleCreate"
    />
  </AppShell>
</template>
