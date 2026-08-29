<script setup>
import { computed, reactive, ref } from 'vue'
import AppShell from '../../../Components/AppShell.vue'
import StatusBadge from '../../../Components/StatusBadge.vue'
import ProducerModal from '../../../Components/ProducerModal.vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, User, MapPin, Hash, Check, Power, Plus, Droplets, Truck } from '@lucide/vue'

const props = defineProps({
  route: Object,
  routes: {
    type: Array,
    default: () => [],
  },
  today: String,
  todayLiters: {
    type: Object,
    default: () => ({}),
  },
})

const form = useForm({
  name: props.route.name || '',
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

const showProducerModal = ref(false)
const savingProducerId = ref(null)

const activeAssignments = computed(() => props.route.active_assignments || [])

const litersDraft = reactive({})
activeAssignments.value.forEach((assignment) => {
  const producerId = assignment.producer?.id
  if (producerId) {
    litersDraft[producerId] = props.todayLiters?.[producerId] ?? props.todayLiters?.[String(producerId)] ?? ''
  }
})

const openProducerModal = () => {
  showProducerModal.value = true
}

const closeProducerModal = () => {
  showProducerModal.value = false
}

const handleCreateProducer = (producerForm) => {
  producerForm.post('/producers', {
    onSuccess: () => {
      closeProducerModal()
    },
  })
}

const saveTodayLiters = (producerId) => {
  const liters = litersDraft[producerId]
  if (liters === '' || liters === null || Number(liters) < 0) {
    return
  }

  savingProducerId.value = producerId
  router.post(`/routes/${props.route.id}/collections`, {
    producer_id: producerId,
    collection_date: props.today,
    liters,
  }, {
    preserveScroll: true,
    onFinish: () => {
      savingProducerId.value = null
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
          <p class="text-[#6E6E73] mt-1">Trayecto de acopio. El propietario se gestiona en Rutero.</p>
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

    <div class="bg-white rounded-2xl border border-[#E5E5E5] p-6 shadow-sm mb-6">
      <h3 class="text-base font-semibold text-[#1D1D1F] mb-3 flex items-center">
        <Truck class="w-4 h-4 mr-2 text-[#007AFF]" />
        Rutero
      </h3>
      <div v-if="route.rutero" class="flex items-center justify-between gap-3">
        <div>
          <p class="font-medium text-[#1D1D1F] flex items-center gap-2">
            <User class="w-4 h-4 text-[#8E8E93]" />
            {{ route.rutero.full_name }}
          </p>
          <p class="text-sm text-[#8E8E93] mt-1">
            {{ route.rutero.phone }} · {{ route.rutero.vehicle_plate }}
          </p>
        </div>
        <Link :href="`/ruteros/${route.rutero.id}`" class="text-sm font-medium text-[#007AFF] hover:text-[#0056CC]">
          Editar en Rutero →
        </Link>
      </div>
      <p v-else class="text-sm text-[#8E8E93]">
        Esta ruta aún no tiene propietario.
        <Link href="/ruteros" class="text-[#007AFF] hover:text-[#0056CC]">Registrar rutero</Link>
      </p>
    </div>

    <div class="bg-white rounded-2xl border border-[#E5E5E5] p-6 shadow-sm">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
          <h3 class="text-base font-semibold text-[#1D1D1F]">Línea de productores</h3>
          <p class="text-xs text-[#8E8E93]">Acopio del día {{ today }} · el pago se consolida cada jueves</p>
        </div>
        <button
          type="button"
          @click="openProducerModal"
          class="inline-flex items-center px-4 py-2 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] text-sm font-medium"
        >
          <Plus class="w-4 h-4 mr-2" />
          Agregar productor
        </button>
      </div>

      <div v-if="activeAssignments.length" class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="text-left text-xs uppercase tracking-wide text-[#8E8E93]">
            <tr>
              <th class="pb-3 font-semibold">Productor</th>
              <th class="pb-3 font-semibold">Teléfono</th>
              <th class="pb-3 font-semibold text-right">Litros hoy</th>
              <th class="pb-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#E5E5E5]">
            <tr v-for="assignment in activeAssignments" :key="assignment.id">
              <td class="py-3 font-medium text-[#1D1D1F]">{{ assignment.producer?.full_name || 'Productor' }}</td>
              <td class="py-3 text-[#8E8E93]">{{ assignment.producer?.phone || '—' }}</td>
              <td class="py-3 text-right">
                <input
                  v-if="assignment.producer"
                  type="number"
                  min="0"
                  step="0.1"
                  v-model="litersDraft[assignment.producer.id]"
                  class="w-28 bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2 px-3 text-sm text-right text-[#1D1D1F]"
                  placeholder="0.0"
                />
              </td>
              <td class="py-3 text-right">
                <button
                  type="button"
                  :disabled="savingProducerId === assignment.producer.id"
                  @click="saveTodayLiters(assignment.producer.id)"
                  class="text-sm font-medium text-[#007AFF] hover:text-[#0056CC] disabled:opacity-50"
                >
                  {{ savingProducerId === assignment.producer.id ? 'Guardando...' : 'Guardar' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else class="text-sm text-[#8E8E93]">Esta ruta aún no tiene productores. Agrega el primero para registrar el acopio diario.</p>
    </div>

    <ProducerModal
      :show="showProducerModal"
      :routes="routes"
      :default-route-id="route.id"
      :return-to="`/routes/${route.id}`"
      @close="closeProducerModal"
      @submit="handleCreateProducer"
    />
  </AppShell>
</template>
