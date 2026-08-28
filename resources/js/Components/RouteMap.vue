<script setup>
import { MapPin, Navigation, Truck } from '@lucide/vue'

const props = defineProps({
  routes: Array,
  collections: Array
})

const mockRoutes = [
  { id: 1, name: 'Ruta Matagalpa', status: 'completed', liters: 2850, points: 12 },
  { id: 2, name: 'Ruta Estelí', status: 'in_progress', liters: 1920, points: 8 },
  { id: 3, name: 'Ruta Jinotega', status: 'pending', liters: 0, points: 15 },
  { id: 4, name: 'Ruta Sébaco', status: 'completed', liters: 3100, points: 18 },
]
</script>

<template>
  <div class="bg-white rounded-2xl border border-[#E5E5E5] p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-display font-semibold text-[#1D1D1F]">Mapa de Rutas</h3>
      <div class="flex items-center space-x-2 text-xs">
        <div class="flex items-center">
          <div class="w-3 h-3 bg-[#34C759] rounded-full mr-1"></div>
          <span class="text-[#6E6E73]">Completada</span>
        </div>
        <div class="flex items-center">
          <div class="w-3 h-3 bg-[#007AFF] rounded-full mr-1"></div>
          <span class="text-[#6E6E73]">En proceso</span>
        </div>
        <div class="flex items-center">
          <div class="w-3 h-3 bg-[#8E8E93] rounded-full mr-1"></div>
          <span class="text-[#6E6E73]">Pendiente</span>
        </div>
      </div>
    </div>
    
    <div class="relative h-80 bg-gradient-to-br from-[#E5F1FF] to-[#F5F5F7] rounded-xl overflow-hidden">
      <!-- Simulated Map Background -->
      <div class="absolute inset-0 opacity-30">
        <svg viewBox="0 0 400 300" class="w-full h-full">
          <path d="M50,150 Q100,50 200,100 T350,150" stroke="#007AFF" stroke-width="2" fill="none" stroke-dasharray="5,5"/>
          <path d="M80,200 Q150,150 250,180 T380,220" stroke="#34C759" stroke-width="2" fill="none"/>
          <path d="M30,100 Q100,80 180,120 T300,90" stroke="#8E8E93" stroke-width="2" fill="none" stroke-dasharray="5,5"/>
        </svg>
      </div>
      
      <!-- Route Points -->
      <div class="absolute inset-0">
        <div 
          v-for="route in mockRoutes" 
          :key="route.id"
          class="absolute transform -translate-x-1/2 -translate-y-1/2 cursor-pointer group"
          :style="{
            left: `${20 + route.id * 20}%`,
            top: `${30 + route.id * 15}%`
          }"
        >
          <div 
            :class="[
              'w-8 h-8 rounded-full flex items-center justify-center shadow-lg transition-all duration-200 group-hover:scale-110',
              route.status === 'completed' ? 'bg-[#34C759]' : route.status === 'in_progress' ? 'bg-[#007AFF]' : 'bg-[#8E8E93]'
            ]"
          >
            <MapPin class="w-4 h-4 text-white" />
          </div>
          
          <!-- Tooltip -->
          <div class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 bg-[#1D1D1F] text-white text-xs rounded-lg px-3 py-2 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
            <p class="font-medium">{{ route.name }}</p>
            <p class="text-[#8E8E93]">{{ route.liters }}L • {{ route.points }} puntos</p>
          </div>
        </div>
      </div>
      
      <!-- Truck Animation -->
      <div class="absolute top-[45%] left-[40%] animate-pulse">
        <div class="bg-[#007AFF] p-2 rounded-full shadow-lg">
          <Truck class="w-5 h-5 text-white" />
        </div>
      </div>
    </div>
    
    <!-- Route List -->
    <div class="mt-4 grid grid-cols-2 gap-2">
      <div 
        v-for="route in mockRoutes"
        :key="route.id"
        class="flex items-center justify-between p-2 bg-[#F5F5F7] rounded-lg"
      >
        <div class="flex items-center">
          <div 
            :class="[
              'w-2 h-2 rounded-full mr-2',
              route.status === 'completed' ? 'bg-[#34C759]' : route.status === 'in_progress' ? 'bg-[#007AFF]' : 'bg-[#8E8E93]'
            ]"
          ></div>
          <span class="text-sm text-[#1D1D1F]">{{ route.name }}</span>
        </div>
        <span class="text-xs text-[#6E6E73]">{{ route.liters }}L</span>
      </div>
    </div>
  </div>
</template>
