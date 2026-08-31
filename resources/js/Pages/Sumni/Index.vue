<script setup>
import { computed, ref } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import { Link } from '@inertiajs/vue3'
import { Search, MapPin, User, Truck, Droplets, UserCog } from '@lucide/vue'

const props = defineProps({
  today: String,
  routes: {
    type: Array,
    default: () => [],
  },
})

const searchQuery = ref('')

const filteredRoutes = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const list = props.routes || []
  if (!query) return list

  return list.filter((route) =>
    [route.code, route.name, route.owner_name, route.driver_name, route.owner_phone, route.driver_phone, route.vehicle_plate, route.vehicle_description]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(query))
  )
})
</script>

<template>
  <AppShell>
    <div class="max-w-2xl mx-auto">
      <div class="mb-6 text-center sm:text-left">
        <p class="text-[11px] tracking-[0.22em] uppercase text-[#007AFF]">Sumni</p>
        <h1 class="text-3xl font-display font-bold text-[#1D1D1F] mt-1">Elige la ruta</h1>
        <p class="text-sm text-[#8E8E93] mt-1">
          Cada ruta tiene su propietario y sus clientes. Hoy {{ today }}
        </p>
      </div>

      <div class="bg-white rounded-[28px] border border-[#E5E5E5] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E5E5E5]">
          <div class="relative">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#8E8E93]" />
            <input
              type="text"
              v-model="searchQuery"
              autofocus
              placeholder="Buscar ruta, propietario, encargado o placa..."
              class="w-full pl-12 pr-4 py-3.5 bg-[#F5F5F7] border-none rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-base text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
        </div>

        <div v-if="filteredRoutes.length" class="divide-y divide-[#E5E5E5]">
          <Link
            v-for="route in filteredRoutes"
            :key="route.id"
            :href="`/sumni/${route.id}`"
            class="block p-5 hover:bg-[#F5F5F7] transition-colors"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-start gap-3 min-w-0">
                <div class="w-11 h-11 bg-[#E5F1FF] rounded-2xl flex items-center justify-center shrink-0">
                  <MapPin class="w-5 h-5 text-[#007AFF]" />
                </div>
                <div class="min-w-0">
                  <p class="text-xs text-[#8E8E93]">{{ route.code }}</p>
                  <p class="font-display font-bold text-[#1D1D1F]">{{ route.name }}</p>
                  <p class="mt-1 text-sm text-[#1D1D1F] flex items-center gap-1.5">
                    <User class="w-3.5 h-3.5 text-[#8E8E93]" />
                    Prop: {{ route.owner_name || 'Sin propietario' }}
                  </p>
                  <p class="text-xs text-[#8E8E93] flex items-center gap-1.5 mt-0.5">
                    <UserCog class="w-3 h-3" />
                    Enc: {{ route.driver_name || 'Sin encargado' }}
                  </p>
                  <p v-if="route.vehicle_description || route.vehicle_plate" class="text-xs text-[#8E8E93] flex items-center gap-1.5 mt-0.5">
                    <Truck class="w-3 h-3" />
                    {{ route.vehicle_description }} · {{ route.vehicle_plate }}
                  </p>
                </div>
              </div>
              <div class="text-right shrink-0">
                <p class="text-sm font-semibold text-[#1D1D1F]">{{ route.clients }} clientes</p>
                <p class="text-xs text-[#8E8E93] mt-1 flex items-center justify-end gap-1">
                  <Droplets class="w-3 h-3" />
                  {{ route.today_visits }} hoy · {{ route.today_liters }} L
                </p>
              </div>
            </div>
          </Link>
        </div>
        <p v-else class="p-8 text-center text-[#8E8E93]">
          {{ searchQuery ? 'No hay rutas que coincidan.' : 'No hay rutas activas para acopio.' }}
        </p>
      </div>
    </div>
  </AppShell>
</template>
