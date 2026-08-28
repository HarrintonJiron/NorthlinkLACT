<script setup>
import AppShell from '../../../Components/AppShell.vue'
import StatusBadge from '../../../Components/StatusBadge.vue'
import EmptyState from '../../../Components/EmptyState.vue'
import { Link } from '@inertiajs/vue3'
import { Droplets, Plus, Search, Filter } from '@lucide/vue'

const props = defineProps({
    collections: Array,
})
</script>

<template>
  <AppShell>
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-display font-bold text-gray-900">Acopio de Leche</h1>
          <p class="text-gray-500 mt-1">Registra y gestiona la recolección diaria de leche</p>
        </div>
        <Link href="/collections/create" class="inline-flex items-center px-4 py-2 bg-[#1E3A5F] text-white rounded-lg hover:bg-[#2D4A6F] transition-colors">
          <Plus class="w-4 h-4 mr-2" />
          Registrar Acopio
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
              placeholder="Buscar acopios..."
              class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2D5A3D] focus:border-transparent text-sm w-64"
            />
          </div>
          <button class="flex items-center px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
            <Filter class="w-4 h-4 mr-2" />
            Filtros
          </button>
        </div>
        <div class="text-sm text-gray-500">
          {{ collections?.length || 0 }} registros
        </div>
      </div>

      <div v-if="collections && collections.length > 0" class="divide-y divide-gray-200">
        <div 
          v-for="collection in collections" 
          :key="collection.id"
          class="p-4 hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="bg-[#E8F4F8] p-3 rounded-lg">
                <Droplets class="w-5 h-5 text-[#1E3A5F]" />
              </div>
              <div>
                <Link :href="`/collections/${collection.id}`" class="font-medium text-gray-900 hover:text-[#1E3A5F]">
                  {{ collection.producer?.full_name }}
                </Link>
                <p class="text-sm text-gray-500 mt-1">
                  Ruta: {{ collection.route?.name }} • {{ collection.collection_date }}
                </p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="text-right">
                <p class="font-display font-semibold text-gray-900">{{ collection.liters }} L</p>
                <p v-if="collection.temperature" class="text-xs text-gray-500">{{ collection.temperature }}°C</p>
              </div>
              <Link :href="`/collections/${collection.id}`" class="text-[#1E3A5F] hover:text-[#2D5A3D]">
                Ver detalles →
              </Link>
            </div>
          </div>
        </div>
      </div>

      <EmptyState
        v-else
        title="No hay registros de acopio"
        description="Registra el primer acopio de leche para iniciar el seguimiento diario."
        :icon="Droplets"
        action-label="Registrar primer acopio"
        action-href="/collections/create"
      />
    </div>
  </AppShell>
</template>
