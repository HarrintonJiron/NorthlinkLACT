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
          <h1 class="text-3xl font-display font-bold text-[#1D1D1F]">Acopio de Leche</h1>
          <p class="text-[#6E6E73] mt-1">Registra y gestiona la recolección diaria de leche</p>
        </div>
        <Link href="/collections/create" class="inline-flex items-center px-5 py-2.5 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] transition-colors font-medium shadow-sm">
          <Plus class="w-4 h-4 mr-2" />
          Registrar Acopio
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
              placeholder="Buscar acopios..."
              class="pl-10 pr-4 py-2 bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-sm w-64 text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
          <button class="flex items-center px-4 py-2 bg-[#F5F5F7] rounded-xl hover:bg-[#E5E5E5] text-sm text-[#1D1D1F] transition-colors">
            <Filter class="w-4 h-4 mr-2" />
            Filtros
          </button>
        </div>
        <div class="text-sm text-[#8E8E93]">
          {{ collections?.length || 0 }} registros
        </div>
      </div>

      <div v-if="collections && collections.length > 0" class="divide-y divide-[#E5E5E5]">
        <div 
          v-for="collection in collections" 
          :key="collection.id"
          class="p-4 hover:bg-[#F5F5F7] transition-colors"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="bg-gradient-to-br from-[#34C759] to-[#30D158] p-3 rounded-xl shadow-sm">
                <Droplets class="w-5 h-5 text-white" />
              </div>
              <div>
                <Link :href="`/collections/${collection.id}`" class="font-medium text-[#1D1D1F] hover:text-[#007AFF]">
                  {{ collection.producer?.full_name }}
                </Link>
                <p class="text-sm text-[#8E8E93] mt-1">
                  Ruta: {{ collection.route?.name }} • {{ collection.collection_date }}
                </p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="text-right">
                <p class="font-display font-semibold text-[#1D1D1F]">{{ collection.liters }} L</p>
                <p v-if="collection.temperature" class="text-xs text-[#8E8E93]">{{ collection.temperature }}°C</p>
              </div>
              <Link :href="`/collections/${collection.id}`" class="text-[#007AFF] hover:text-[#0056CC] font-medium">
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
