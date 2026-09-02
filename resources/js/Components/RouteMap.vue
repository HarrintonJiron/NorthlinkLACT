<script setup>
import { MapPin, Truck } from '@lucide/vue'

defineProps({
  routes: {
    type: Array,
    required: true,
  },
})

const numberFormatter = new Intl.NumberFormat('es-NI', { maximumFractionDigits: 2 })

const positionFor = (index, total) => {
  const columns = Math.max(1, Math.ceil(Math.sqrt(total)))
  const row = Math.floor(index / columns)
  const column = index % columns
  const rows = Math.max(1, Math.ceil(total / columns))

  return {
    left: `${((column + 1) / (columns + 1)) * 100}%`,
    top: `${((row + 1) / (rows + 1)) * 100}%`,
  }
}
</script>

<template>
  <div class="rounded-2xl border border-[#E5E5E5] bg-white p-6 shadow-sm">
    <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
      <div>
        <h3 class="font-display text-lg font-semibold text-[#1D1D1F]">Rutas de acopio</h3>
        <p class="text-xs text-[#8E8E93]">Vista operativa del estado de hoy</p>
      </div>
      <div class="flex flex-wrap items-center gap-3 text-xs">
        <span class="flex items-center gap-1 text-[#6E6E73]"><i class="size-3 rounded-full bg-[#34C759]" />Completada</span>
        <span class="flex items-center gap-1 text-[#6E6E73]"><i class="size-3 rounded-full bg-[#007AFF]" />En proceso</span>
        <span class="flex items-center gap-1 text-[#6E6E73]"><i class="size-3 rounded-full bg-[#8E8E93]" />Pendiente</span>
      </div>
    </div>

    <div v-if="routes.length" class="relative h-80 overflow-hidden rounded-xl bg-gradient-to-br from-[#E5F1FF] to-[#F5F5F7]">
      <div class="absolute inset-0 opacity-30">
        <svg viewBox="0 0 400 300" class="size-full" aria-hidden="true">
          <path d="M30,80 Q120,20 205,95 T380,80" stroke="#007AFF" stroke-width="2" fill="none" stroke-dasharray="5,5" />
          <path d="M25,210 Q115,135 225,195 T390,205" stroke="#34C759" stroke-width="2" fill="none" />
          <path d="M45,145 Q130,105 210,150 T350,135" stroke="#8E8E93" stroke-width="2" fill="none" stroke-dasharray="5,5" />
        </svg>
      </div>

      <div
        v-for="(route, index) in routes"
        :key="route.id"
        class="group absolute -translate-x-1/2 -translate-y-1/2"
        :style="positionFor(index, routes.length)"
      >
        <div
          class="flex size-8 items-center justify-center rounded-full shadow-lg transition-transform duration-200 group-hover:scale-110"
          :class="route.status === 'completed' ? 'bg-[#34C759]' : route.status === 'in_progress' ? 'bg-[#007AFF]' : 'bg-[#8E8E93]'"
        >
          <MapPin class="size-4 text-white" />
        </div>
        <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-[#1D1D1F] px-3 py-2 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100">
          <p class="font-medium">{{ route.name }}</p>
          <p class="text-[#D1D1D6]">{{ numberFormatter.format(route.liters) }} L · {{ route.attended }}/{{ route.producers }} productores</p>
        </div>
      </div>

      <div v-if="routes.some((route) => route.status === 'in_progress')" class="absolute left-[46%] top-[46%] animate-pulse rounded-full bg-[#007AFF] p-2 shadow-lg">
        <Truck class="size-5 text-white" />
      </div>
    </div>

    <div v-else class="flex h-80 flex-col items-center justify-center gap-2 rounded-xl bg-[#F5F5F7] text-center">
      <MapPin class="size-8 text-[#8E8E93]" />
      <p class="font-medium text-[#1D1D1F]">No hay rutas activas</p>
      <p class="text-sm text-[#8E8E93]">Activa o registra una ruta para verla aquí.</p>
    </div>

    <div v-if="routes.length" class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
      <div v-for="route in routes" :key="route.id" class="flex items-center justify-between rounded-lg bg-[#F5F5F7] p-2">
        <div class="flex min-w-0 items-center gap-2">
          <span class="size-2 shrink-0 rounded-full" :class="route.status === 'completed' ? 'bg-[#34C759]' : route.status === 'in_progress' ? 'bg-[#007AFF]' : 'bg-[#8E8E93]'" />
          <span class="truncate text-sm text-[#1D1D1F]">{{ route.name }}</span>
        </div>
        <span class="shrink-0 text-xs text-[#6E6E73]">{{ numberFormatter.format(route.liters) }} L</span>
      </div>
    </div>
  </div>
</template>
