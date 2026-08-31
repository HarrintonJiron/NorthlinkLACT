<script setup>
import { computed, ref } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import RuteroModal from '../../Components/RuteroModal.vue'
import StatusBadge from '../../Components/StatusBadge.vue'
import { Link, router } from '@inertiajs/vue3'
import { Truck, Plus, Search, User, Phone, MapPin, UserCog, Pencil, Power } from '@lucide/vue'

const props = defineProps({
  rutero: Object,
  returnTo: {
    type: String,
    default: null,
  },
})

const showEditModal = ref(false)

const backHref = computed(() => props.returnTo || '/ruteros')
const backLabel = computed(() => (props.returnTo ? 'Volver a la ruta' : 'Volver a ruteros'))

const handleUpdate = (form) => {
  form.transform((data) => ({
    ...data,
    return_to: props.returnTo || undefined,
  })).put(`/ruteros/${props.rutero.id}`, {
    onSuccess: () => {
      showEditModal.value = false
    },
  })
}

const toggleActive = () => {
  const action = props.rutero.active ? 'desactivar' : 'activar'
  if (!confirm(`¿Seguro que quieres ${action} este rutero?`)) {
    return
  }
  router.patch(`/ruteros/${props.rutero.id}/toggle`)
}
</script>

<template>
  <AppShell>
    <div class="mb-8">
      <Link :href="backHref" class="inline-flex items-center text-[#007AFF] hover:text-[#0056CC] mb-4">
        ← {{ backLabel }}
      </Link>
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-3xl font-display font-bold text-[#1D1D1F]">{{ rutero.owner_name }}</h1>
            <StatusBadge
              :status="rutero.active ? 'completed' : 'cancelled'"
              :label="rutero.active ? 'Activo' : 'Inactivo'"
            />
          </div>
          <p class="text-[#6E6E73] mt-1">
            {{ rutero.route ? `${rutero.route.code} — ${rutero.route.name}` : 'Sin ruta' }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            @click="showEditModal = true"
            class="inline-flex items-center px-5 py-2.5 rounded-xl font-medium shadow-sm bg-[#E5F1FF] text-[#007AFF] hover:bg-[#D6E9FF]"
          >
            <Pencil class="w-4 h-4 mr-2" />
            Editar
          </button>
          <button
            type="button"
            @click="toggleActive"
            :class="[
              'inline-flex items-center px-5 py-2.5 rounded-xl font-medium shadow-sm',
              rutero.active
                ? 'bg-[#FFE5E5] text-[#FF3B30] hover:bg-[#FFD1D1]'
                : 'bg-[#E8F8E8] text-[#34C759] hover:bg-[#D4F4D4]',
            ]"
          >
            <Power class="w-4 h-4 mr-2" />
            {{ rutero.active ? 'Desactivar' : 'Activar' }}
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white rounded-2xl border border-[#E5E5E5] p-6 shadow-sm">
        <h3 class="text-base font-semibold text-[#1D1D1F] mb-4 flex items-center">
          <User class="w-4 h-4 mr-2 text-[#007AFF]" />
          Propietario del camión
        </h3>
        <dl class="space-y-3 text-sm">
          <div>
            <dt class="text-[#8E8E93]">Nombre</dt>
            <dd class="font-medium text-[#1D1D1F]">{{ rutero.owner_name }}</dd>
          </div>
          <div>
            <dt class="text-[#8E8E93]">Cédula</dt>
            <dd class="text-[#1D1D1F]">{{ rutero.owner_identity_number }}</dd>
          </div>
          <div>
            <dt class="text-[#8E8E93]">Celular</dt>
            <dd class="text-[#1D1D1F]">{{ rutero.owner_phone }}</dd>
          </div>
          <div>
            <dt class="text-[#8E8E93]">Vehículo</dt>
            <dd class="text-[#1D1D1F]">{{ rutero.vehicle_description }} · {{ rutero.vehicle_plate }}</dd>
          </div>
        </dl>
      </div>

      <div class="bg-white rounded-2xl border border-[#E5E5E5] p-6 shadow-sm">
        <h3 class="text-base font-semibold text-[#1D1D1F] mb-4 flex items-center">
          <UserCog class="w-4 h-4 mr-2 text-[#007AFF]" />
          Encargado de la ruta
        </h3>
        <dl class="space-y-3 text-sm">
          <div>
            <dt class="text-[#8E8E93]">Nombre</dt>
            <dd class="font-medium text-[#1D1D1F]">{{ rutero.driver_name }}</dd>
          </div>
          <div>
            <dt class="text-[#8E8E93]">Cédula</dt>
            <dd class="text-[#1D1D1F]">{{ rutero.driver_identity_number }}</dd>
          </div>
          <div>
            <dt class="text-[#8E8E93]">Celular</dt>
            <dd class="text-[#1D1D1F]">{{ rutero.driver_phone }}</dd>
          </div>
          <div>
            <dt class="text-[#8E8E93]">Ruta asignada</dt>
            <dd class="text-[#1D1D1F] flex items-center gap-1">
              <MapPin class="w-3.5 h-3.5" />
              <template v-if="rutero.route">
                {{ rutero.route.code }} — {{ rutero.route.name }}
                <Link :href="`/routes/${rutero.route.id}`" class="ml-2 text-[#007AFF] hover:text-[#0056CC] text-xs">
                  Ver ruta →
                </Link>
              </template>
              <span v-else>Sin ruta · asigna desde Rutas</span>
            </dd>
          </div>
        </dl>
      </div>
    </div>

    <RuteroModal
      :show="showEditModal"
      :rutero="rutero"
      @close="showEditModal = false"
      @submit="handleUpdate"
    />
  </AppShell>
</template>
