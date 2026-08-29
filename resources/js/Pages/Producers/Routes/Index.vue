<script setup>
import { computed, ref } from 'vue'
import AppShell from '../../../Components/AppShell.vue'
import RouteModal from '../../../Components/RouteModal.vue'
import ProducerModal from '../../../Components/ProducerModal.vue'
import RouteStatsPanel from '../../../Components/RouteStatsPanel.vue'
import { Link } from '@inertiajs/vue3'
import { MapPin, Plus, Search, Filter, Truck } from '@lucide/vue'

const props = defineProps({
  routes: Array,
  nextCode: String,
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      active: 0,
      inactive: 0,
      new_this_month: 0,
      trends: {},
      monthly: [],
    }),
  },
})

const showCreateModal = ref(false)
const showProducerModal = ref(false)
const defaultRouteId = ref('')
const searchQuery = ref('')

const filteredRoutes = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return props.routes || []

  return (props.routes || []).filter((route) => {
    return [
      route.code,
      route.name,
      route.rutero?.full_name,
    ]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(query))
  })
})

const openCreateModal = () => {
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
}

const handleCreate = (form) => {
  form.post('/routes', {
    onSuccess: () => {
      closeCreateModal()
    },
  })
}

const openProducerModal = (routeId) => {
  defaultRouteId.value = String(routeId)
  showProducerModal.value = true
}

const closeProducerModal = () => {
  showProducerModal.value = false
  defaultRouteId.value = ''
}

const handleCreateProducer = (form) => {
  form.post('/producers', {
    onSuccess: () => {
      closeProducerModal()
    },
  })
}

</script>

<template>
  <AppShell>
    <RouteStatsPanel
      :stats="stats"
      @nueva-ruta="openCreateModal"
    />

    <div id="rutas-listado" class="rounded-[28px] border border-[#E5E5E5] bg-white overflow-hidden shadow-sm">
      <div class="p-4 border-b border-[#E5E5E5] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center space-x-2">
          <div class="relative flex-1 sm:flex-none">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Buscar rutas..."
              class="pl-10 pr-4 py-2 bg-[#F5F5F7] border-none rounded-full focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-sm w-full sm:w-64 text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
          <button class="flex items-center px-4 py-2 bg-[#F5F5F7] rounded-full hover:bg-[#EFEFF4] text-sm text-[#6E6E73] transition-colors">
            <Filter class="w-4 h-4 mr-2" />
            Filtros
          </button>
        </div>
        <div class="text-sm text-[#8E8E93]">
          {{ filteredRoutes.length }} rutas
        </div>
      </div>

      <div v-if="filteredRoutes.length > 0" class="divide-y divide-[#E5E5E5]">
        <div
          v-for="route in filteredRoutes"
          :key="route.id"
          class="p-4 hover:bg-[#F5F5F7] transition-colors"
        >
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center space-x-4">
              <div class="bg-[#E5F1FF] p-3 rounded-xl">
                <MapPin class="w-5 h-5 text-[#007AFF]" />
              </div>
              <div>
                <Link :href="`/routes/${route.id}`" class="font-medium text-[#1D1D1F] hover:text-[#007AFF]">
                  {{ route.code }} — {{ route.name }}
                </Link>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-[#8E8E93] mt-1">
                  <span v-if="route.rutero" class="inline-flex items-center">
                    <Truck class="w-3.5 h-3.5 mr-1" />
                    Rutero: {{ route.rutero.full_name }}
                  </span>
                  <span v-else>Sin rutero asignado</span>
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-3 pl-14 sm:pl-0">
              <span
                :class="route.active ? 'bg-[#E8F8E8] text-[#34C759]' : 'bg-[#F5F5F7] text-[#8E8E93]'"
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
              >
                {{ route.active ? 'Activa' : 'Inactiva' }}
              </span>
              <button
                type="button"
                @click="openProducerModal(route.id)"
                class="inline-flex items-center text-sm font-medium text-[#007AFF] hover:text-[#0056CC]"
              >
                <Plus class="w-4 h-4 mr-1" />
                Agregar productor
              </button>
              <Link :href="`/routes/${route.id}`" class="text-[#007AFF] hover:text-[#0056CC] font-medium">
                Ver detalles →
              </Link>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-12">
        <MapPin class="w-12 h-12 text-[#007AFF]/40 mx-auto mb-3" />
        <p class="text-[#1D1D1F] font-medium mb-1">{{ searchQuery ? 'Sin resultados' : 'No hay rutas registradas' }}</p>
        <p class="text-sm text-[#8E8E93] mb-4">
          {{ searchQuery ? 'No encontramos rutas que coincidan con la búsqueda.' : 'Comienza creando una nueva ruta de acopio.' }}
        </p>
        <button
          v-if="!searchQuery"
          type="button"
          @click="openCreateModal"
          class="inline-flex items-center px-4 py-2 border border-[#007AFF] text-[#007AFF] rounded-xl hover:bg-[#E5F1FF] transition-colors text-sm font-medium"
        >
          <Plus class="w-4 h-4 mr-2" />
          Crear primera ruta
        </button>
      </div>
    </div>

    <RouteModal
      :show="showCreateModal"
      :next-code="nextCode"
      @close="closeCreateModal"
      @submit="handleCreate"
    />

    <ProducerModal
      :show="showProducerModal"
      :routes="routes"
      :default-route-id="defaultRouteId"
      :return-to="defaultRouteId ? `/routes/${defaultRouteId}` : '/routes'"
      @close="closeProducerModal"
      @submit="handleCreateProducer"
    />
  </AppShell>
</template>
