<script setup>
import AppShell from '../../../Components/AppShell.vue'
import StatusBadge from '../../../Components/StatusBadge.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { Link } from '@inertiajs/vue3'
import { MapPin, Plus, Search, Filter } from '@lucide/vue'

const props = defineProps({
    routes: Array,
})
</script>

<template>
  <AppShell>
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-display font-bold text-gray-900">Rutas de Acopio</h1>
          <p class="text-gray-500 mt-1">Gestiona las rutas de recolección de leche</p>
        </div>
        <Link href="/routes/create" class="inline-flex items-center px-4 py-2 bg-[#1E3A5F] text-white rounded-lg hover:bg-[#2D4A6F] transition-colors">
          <Plus class="w-4 h-4 mr-2" />
          Nueva Ruta
        </Link>
      </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200">
      <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              type="text"
              placeholder="Buscar rutas..."
              class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2D5A3D] focus:border-transparent text-sm w-64"
            />
          </div>
          <button class="flex items-center px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
            <Filter class="w-4 h-4 mr-2" />
            Filtros
          </button>
        </div>
        <div class="text-sm text-gray-500">
          {{ routes?.length || 0 }} rutas
        </div>
      </div>

      <div v-if="routes && routes.length > 0" class="divide-y divide-gray-200">
        <div 
          v-for="route in routes" 
          :key="route.id"
          class="p-4 hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="bg-[#E8F4F8] p-3 rounded-lg">
                <MapPin class="w-5 h-5 text-[#1E3A5F]" />
              </div>
              <div>
                <Link :href="`/routes/${route.id}`" class="font-medium text-gray-900 hover:text-[#1E3A5F]">
                  {{ route.code }} - {{ route.name }}
                </Link>
                <p class="text-sm text-gray-500 mt-1">
                  {{ route.company?.name }} - {{ route.plant?.name }}
                </p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <StatusBadge 
                :status="route.active ? 'completed' : 'cancelled'"
                :label="route.active ? 'Activa' : 'Inactiva'"
              />
              <Link :href="`/routes/${route.id}`" class="text-[#1E3A5F] hover:text-[#2D5A3D]">
                Ver detalles →
              </Link>
            </div>
          </div>
        </div>
      </div>

      <EmptyState
        v-else
        title="No hay rutas registradas"
        description="Comienza creando una nueva ruta de acopio para organizar la recolección de leche."
        :icon="MapPin"
        action-label="Crear primera ruta"
        action-href="/routes/create"
      />
    </div>
  </AppShell>
</template>
