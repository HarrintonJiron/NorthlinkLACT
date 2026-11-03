<script setup>
import { computed, ref } from 'vue'
import FieldAppShell from '../../Components/FieldAppShell.vue'
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
const maxVisibleItems = 30

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

const visibleRoutes = computed(() => filteredRoutes.value.slice(0, maxVisibleItems))
</script>

<template>
  <FieldAppShell>
    <div class="mx-auto w-full max-w-2xl">
      <div class="mb-4 px-1 sm:mb-6">
        <p class="text-[11px] font-bold tracking-[0.18em] uppercase text-[#176B45]">Jornada de recolección</p>
        <h1 class="mt-1 text-2xl font-extrabold text-[#17221C] sm:text-3xl">Selecciona una ruta</h1>
        <p class="mt-1 text-sm leading-snug text-[#688075]">
          Cada ruta tiene su propietario y sus clientes. Hoy {{ today }}
        </p>
      </div>

      <div class="overflow-hidden rounded-2xl border border-[#DCE5DF] bg-white shadow-sm sm:rounded-[28px]">
        <div class="border-b border-[#E3EAE6] p-3 sm:p-4">
          <div class="relative">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#8E8E93]" />
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Buscar ruta, propietario, encargado o placa..."
              class="h-13 w-full rounded-xl border border-[#DCE5DF] bg-[#F4F7F5] py-3 pl-12 pr-4 text-base text-[#17221C] outline-none placeholder:text-[#829087] focus:border-[#176B45] focus:ring-2 focus:ring-[#176B45]/15 sm:rounded-2xl"
            />
          </div>
        </div>

        <div v-if="filteredRoutes.length" class="border-b border-[#E5E5E5] px-4 py-2 text-xs text-[#8E8E93]">
          Mostrando {{ visibleRoutes.length }} de {{ filteredRoutes.length }} rutas
        </div>

        <div v-if="filteredRoutes.length" class="divide-y divide-[#E3EAE6] sm:max-h-[38rem] sm:overflow-y-auto">
          <Link
            v-for="route in visibleRoutes"
            :key="route.id"
            :href="`/sumni/${route.id}`"
            class="block p-4 active:bg-[#EEF5F1] sm:p-5 sm:hover:bg-[#F4F7F5]"
          >
            <div class="flex flex-col gap-3 min-[390px]:flex-row min-[390px]:items-start min-[390px]:justify-between">
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
              <div class="ml-14 flex shrink-0 items-center justify-between gap-3 min-[390px]:ml-0 min-[390px]:block min-[390px]:text-right">
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
  </FieldAppShell>
</template>
