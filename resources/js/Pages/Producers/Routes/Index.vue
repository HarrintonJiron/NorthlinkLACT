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
          <h1 class="text-3xl font-display font-bold text-[#1D1D1F]">Rutas de Acopio</h1>
          <p class="text-[#6E6E73] mt-1">Gestiona las rutas de recolección de leche</p>
        </div>
        <Link href="/routes/create" class="inline-flex items-center px-5 py-2.5 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] transition-colors font-medium shadow-sm">
          <Plus class="w-4 h-4 mr-2" />
          Nueva Ruta
        </Link>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-[#E5E5E5] shadow-sm">
      <div class="p-4 border-b border-[#E5E5E5] flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="text"
              placeholder="Buscar rutas..."
              class="pl-10 pr-4 py-2 bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-sm w-64 text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
          <button class="flex items-center px-4 py-2 bg-[#F5F5F7] rounded-xl hover:bg-[#E5E5E5] text-sm text-[#1D1D1F] transition-colors">
            <Filter class="w-4 h-4 mr-2" />
            Filtros
          </button>
        </div>
        <div class="text-sm text-[#8E8E93]">
          {{ routes?.length || 0 }} rutas
        </div>
      </div>

      <div v-if="routes && routes.length > 0" class="divide-y divide-[#E5E5E5]">
        <div 
          v-for="route in routes" 
          :key="route.id"
          class="p-4 hover:bg-[#F5F5F7] transition-colors"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="bg-gradient-to-br from-[#007AFF] to-[#5AC8FA] p-3 rounded-xl shadow-sm">
                <MapPin class="w-5 h-5 text-white" />
              </div>
              <div>
                <Link :href="`/routes/${route.id}`" class="font-medium text-[#1D1D1F] hover:text-[#007AFF]">
                  {{ route.code }} - {{ route.name }}
                </Link>
                <p class="text-sm text-[#8E8E93] mt-1">
                  {{ route.company?.name }} - {{ route.plant?.name }}
                </p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <StatusBadge 
                :status="route.active ? 'completed' : 'cancelled'"
                :label="route.active ? 'Activa' : 'Inactiva'"
              />
              <Link :href="`/routes/${route.id}`" class="text-[#007AFF] hover:text-[#0056CC] font-medium">
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
