<script setup>
import { computed, ref, watch } from 'vue'
import AppShell from '../../../Components/AppShell.vue'
import StatusBadge from '../../../Components/StatusBadge.vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, MapPin, Hash, Check, Power, Plus, Droplets, Truck, UserCog } from '@lucide/vue'

const props = defineProps({
  route: Object,
  availableRuteros: {
    type: Array,
    default: () => [],
  },
})

const form = useForm({
  name: props.route.name || '',
})

const currentRuteroId = computed(() =>
  props.route.rutero?.id ? String(props.route.rutero.id) : ''
)

const selectedRuteroId = ref(currentRuteroId.value)
const savingRutero = ref(false)

watch(currentRuteroId, (value) => {
  selectedRuteroId.value = value
})

const ruteroOptions = computed(() => {
  const options = (props.availableRuteros || []).filter((rutero) => {
    if (!rutero.route_id) {
      return true
    }

    return String(rutero.id) === currentRuteroId.value
  })

  return options.sort((a, b) => a.owner_name.localeCompare(b.owner_name))
})

const hasRuteroChange = computed(() => selectedRuteroId.value !== currentRuteroId.value)

const selectedRuteroPreview = computed(() => {
  if (!selectedRuteroId.value) {
    return null
  }

  if (selectedRuteroId.value === currentRuteroId.value && props.route.rutero) {
    return props.route.rutero
  }

  return ruteroOptions.value.find((rutero) => String(rutero.id) === selectedRuteroId.value) || null
})

const submit = () => {
  form.put(`/routes/${props.route.id}`)
}

const toggleActive = () => {
  const action = props.route.active ? 'desactivar' : 'activar'
  if (!confirm(`¿Seguro que quieres ${action} la ruta ${props.route.code}?`)) {
    return
  }
  router.patch(`/routes/${props.route.id}/toggle`)
}

const applyRuteroSelection = () => {
  if (!hasRuteroChange.value || savingRutero.value) {
    return
  }

  savingRutero.value = true

  if (!selectedRuteroId.value) {
    router.delete(`/routes/${props.route.id}/rutero`, {
      preserveScroll: true,
      onFinish: () => {
        savingRutero.value = false
      },
    })

    return
  }

  router.post(`/routes/${props.route.id}/assign-rutero`, {
    rutero_id: selectedRuteroId.value,
  }, {
    preserveScroll: true,
    onFinish: () => {
      savingRutero.value = false
    },
  })
}
</script>

<template>
  <AppShell>
    <div class="mb-8">
      <Link href="/routes" class="inline-flex items-center text-[#007AFF] hover:text-[#0056CC] mb-4">
        <ArrowLeft class="w-4 h-4 mr-2" />
        Volver a rutas
      </Link>
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-3xl font-display font-bold text-[#1D1D1F]">{{ route.code }} — {{ route.name }}</h1>
            <StatusBadge
              :status="route.active ? 'completed' : 'cancelled'"
              :label="route.active ? 'Activa' : 'Inactiva'"
            />
          </div>
          <p class="text-[#6E6E73] mt-1">Elige quién opera esta ruta o déjala libre.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Link
            v-if="route.active"
            :href="`/sumni/${route.id}`"
            class="inline-flex items-center px-5 py-2.5 rounded-xl font-medium shadow-sm bg-[#E5F1FF] text-[#007AFF] hover:bg-[#D6E9FF]"
          >
            <Droplets class="w-4 h-4 mr-2" />
            Abrir Sumni
          </Link>
          <button
            type="button"
            @click="toggleActive"
            :class="[
              'inline-flex items-center px-5 py-2.5 rounded-xl font-medium shadow-sm transition-colors',
              route.active
                ? 'bg-[#FFE5E5] text-[#FF3B30] hover:bg-[#FFD1D1]'
                : 'bg-[#E8F8E8] text-[#34C759] hover:bg-[#D4F4D4]',
            ]"
          >
            <Power class="w-4 h-4 mr-2" />
            {{ route.active ? 'Desactivar ruta' : 'Activar ruta' }}
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="$page.props.flash?.success"
      class="bg-[#E8F8E8] border border-[#34C759] text-[#1D7A32] px-4 py-3 rounded-xl mb-6"
    >
      {{ $page.props.flash.success }}
    </div>
    <div
      v-if="$page.props.flash?.error"
      class="bg-[#FFE5E5] border border-[#FF3B30] text-[#FF3B30] px-4 py-3 rounded-xl mb-6"
    >
      {{ $page.props.flash.error }}
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-2xl border border-[#E5E5E5] p-6 shadow-sm mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">ID de ruta</label>
          <div class="relative">
            <Hash class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="text"
              :value="route.code"
              disabled
              class="pl-10 w-full bg-[#EFEFF4] border-none rounded-xl py-2.5 px-3 text-sm text-[#6E6E73] cursor-not-allowed"
            />
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Nombre de la ruta *</label>
          <div class="relative">
            <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              type="text"
              v-model="form.name"
              required
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F]"
            />
          </div>
          <p v-if="form.errors.name" class="text-xs text-[#FF3B30] mt-1">{{ form.errors.name }}</p>
        </div>
      </div>
      <div class="flex items-center justify-end mt-6 pt-4 border-t border-[#E5E5E5]">
        <button
          type="submit"
          :disabled="form.processing"
          class="inline-flex items-center px-5 py-2.5 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-50 font-medium shadow-sm"
        >
          <Check v-if="!form.processing" class="w-4 h-4 mr-2" />
          {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
        </button>
      </div>
    </form>

    <div class="bg-white rounded-2xl border border-[#E5E5E5] p-6 shadow-sm">
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-5">
        <div>
          <h3 class="text-base font-semibold text-[#1D1D1F] flex items-center">
            <Truck class="w-4 h-4 mr-2 text-[#007AFF]" />
            Rutero de la ruta
          </h3>
          <p class="text-sm text-[#8E8E93] mt-1">
            Elige un rutero disponible o deja la ruta libre.
          </p>
        </div>
        <Link
          href="/ruteros"
          class="inline-flex items-center px-4 py-2 border border-[#007AFF] text-[#007AFF] rounded-xl hover:bg-[#E5F1FF] text-sm font-medium shrink-0"
        >
          <Plus class="w-4 h-4 mr-2" />
          Nuevo rutero
        </Link>
      </div>

      <div
        v-if="route.rutero && !hasRuteroChange"
        class="rounded-2xl bg-[#F5F5F7] p-4 mb-5"
      >
        <p class="text-xs uppercase tracking-wide text-[#8E8E93] mb-2">Asignado ahora</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
          <div>
            <p class="font-medium text-[#1D1D1F]">{{ route.rutero.owner_name }}</p>
            <p class="text-[#8E8E93] mt-0.5">{{ route.rutero.vehicle_description }} · {{ route.rutero.vehicle_plate }}</p>
          </div>
          <div>
            <p class="text-[#1D1D1F] flex items-center gap-1">
              <UserCog class="w-3.5 h-3.5 text-[#8E8E93]" />
              {{ route.rutero.driver_name }}
            </p>
            <p class="text-[#8E8E93] mt-0.5">{{ route.rutero.driver_phone }}</p>
          </div>
        </div>
        <Link
          :href="`/ruteros/${route.rutero.id}?return_to=${encodeURIComponent(`/routes/${route.id}`)}`"
          class="inline-block mt-3 text-sm font-medium text-[#007AFF] hover:text-[#0056CC]"
        >
          Ver ficha del rutero →
        </Link>
      </div>

      <div
        v-else-if="!route.rutero && !hasRuteroChange"
        class="rounded-2xl border border-dashed border-[#E5E5E5] p-4 mb-5 text-sm text-[#8E8E93]"
      >
        Esta ruta está libre. Selecciona un rutero abajo para asignarlo.
      </div>

      <div v-if="selectedRuteroPreview && hasRuteroChange" class="rounded-2xl bg-[#E5F1FF] p-4 mb-5 text-sm">
        <p class="text-xs uppercase tracking-wide text-[#007AFF] mb-2">Vista previa del cambio</p>
        <p class="font-medium text-[#1D1D1F]">{{ selectedRuteroPreview.owner_name }}</p>
        <p class="text-[#6E6E73] mt-0.5">
          Encargado: {{ selectedRuteroPreview.driver_name }}
          <span v-if="selectedRuteroPreview.vehicle_plate"> · {{ selectedRuteroPreview.vehicle_plate }}</span>
        </p>
      </div>

      <div v-if="!selectedRuteroId && hasRuteroChange" class="rounded-2xl bg-[#FFF8E5] border border-[#FFE5A3] p-4 mb-5 text-sm text-[#8A6D1F]">
        La ruta quedará libre, sin rutero asignado.
      </div>

      <div class="space-y-3">
        <div>
          <label class="block text-xs font-medium text-[#1D1D1F] mb-1.5">Rutero</label>
          <select
            v-model="selectedRuteroId"
            class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-3 text-sm text-[#1D1D1F]"
          >
            <option value="">Sin rutero — dejar ruta libre</option>
            <option
              v-for="rutero in ruteroOptions"
              :key="rutero.id"
              :value="String(rutero.id)"
            >
              {{ rutero.owner_name }} · Enc: {{ rutero.driver_name }} · {{ rutero.vehicle_plate }}
              <template v-if="String(rutero.id) === currentRuteroId"> (actual)</template>
            </option>
          </select>
          <p v-if="!ruteroOptions.length && !route.rutero" class="text-xs text-[#8E8E93] mt-2">
            No hay ruteros disponibles.
            <Link href="/ruteros" class="text-[#007AFF] hover:text-[#0056CC]">Regístralos aquí</Link>
            y vuelve a asignar.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-1">
          <button
            type="button"
            @click="applyRuteroSelection"
            :disabled="!hasRuteroChange || savingRutero"
            class="inline-flex items-center px-5 py-2.5 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-40 text-sm font-medium"
          >
            {{ savingRutero ? 'Guardando...' : 'Guardar rutero' }}
          </button>
          <button
            v-if="hasRuteroChange"
            type="button"
            @click="selectedRuteroId = currentRuteroId"
            class="inline-flex items-center px-4 py-2.5 text-[#6E6E73] hover:text-[#1D1D1F] text-sm font-medium"
          >
            Cancelar
          </button>
        </div>
      </div>
    </div>
  </AppShell>
</template>
