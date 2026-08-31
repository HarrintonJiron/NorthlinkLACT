<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import SumniProducerModal from '../../Components/SumniProducerModal.vue'
import SumniVoucherModal from '../../Components/SumniVoucherModal.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Search, Droplets, Check, ArrowLeft, User, Truck, UserCog, Plus, Lock, Printer } from '@lucide/vue'

const props = defineProps({
  today: String,
  route: {
    type: Object,
    required: true,
  },
  clients: {
    type: Array,
    default: () => [],
  },
  selectProducerId: {
    type: Number,
    default: null,
  },
  lastRecordedProducerId: {
    type: Number,
    default: null,
  },
})

const searchQuery = ref('')
const selected = ref(null)
const showNewClientModal = ref(false)
const showVoucher = ref(false)
const voucher = ref(null)

const form = useForm({
  producer_id: '',
  liters: '',
  temperature: '25',
})

const pendingClients = computed(() =>
  (props.clients || []).filter((client) => !client.recorded_today)
)

const filteredClients = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const list = pendingClients.value
  if (!query) return list

  return list.filter((client) =>
    [client.full_name, client.identity_number, client.phone, client.code]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(query))
  )
})

const recordedToday = computed(() =>
  (props.clients || []).filter((client) => client.recorded_today)
)

const lastRecordedClient = computed(() =>
  recordedToday.value.find((client) => client.id === props.lastRecordedProducerId) || null
)

const buildVoucher = (client) => ({
  date: props.today,
  producer_name: client.full_name,
  producer_code: client.code,
  identity_number: client.identity_number,
  liters: client.today_liters,
  density: client.today_density,
  route_code: props.route.code,
  route_name: props.route.name,
  driver_name: props.route.driver_name,
})

const openVoucher = (client) => {
  voucher.value = buildVoucher(client)
  showVoucher.value = true
}

const openClient = (client) => {
  selected.value = client
  form.producer_id = client.id
  form.liters = ''
  form.temperature = '25'
  form.clearErrors()
}

const clearSelection = () => {
  selected.value = null
  form.reset()
  form.clearErrors()
}

const autoSelectProducer = () => {
  if (!props.selectProducerId) return

  const client = props.clients.find((item) => item.id === props.selectProducerId)
  if (client && !client.recorded_today) {
    openClient(client)
  }
}

const openLastRecordedVoucher = () => {
  if (lastRecordedClient.value) {
    openVoucher(lastRecordedClient.value)
  }
}

onMounted(() => {
  autoSelectProducer()
  if (lastRecordedClient.value) {
    openVoucher(lastRecordedClient.value)
  }
})

watch(() => props.selectProducerId, autoSelectProducer)
watch(() => props.clients, autoSelectProducer)
watch(() => props.lastRecordedProducerId, () => {
  if (lastRecordedClient.value) {
    openVoucher(lastRecordedClient.value)
  }
})

const densityValue = computed(() => {
  const value = parseFloat(form.temperature)
  return Number.isFinite(value) ? value : null
})

const densityHint = computed(() => {
  const value = densityValue.value
  if (value === null) return null

  if (value < 25) {
    return { text: 'Menor a 25 °C: posible agua en la leche', tone: 'warning' }
  }

  if (value > 25) {
    return { text: 'Mayor a 25 °C: la leche tiende a cortarse', tone: 'danger' }
  }

  return { text: '25 °C: buena leche', tone: 'good' }
})

const densityBadge = (value) => {
  if (value == null) return { label: '—', class: 'text-[#8E8E93]' }
  if (value < 25) return { label: `${value} °C · agua`, class: 'text-[#FF9500]' }
  if (value > 25) return { label: `${value} °C · corta`, class: 'text-[#FF3B30]' }
  return { label: `${value} °C · buena`, class: 'text-[#34C759]' }
}

const submit = () => {
  form.post(`/sumni/${props.route.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      selected.value = null
      form.reset()
      searchQuery.value = ''
    },
  })
}
</script>

<template>
  <AppShell>
    <div class="max-w-xl mx-auto">
      <Link
        href="/sumni"
        class="inline-flex items-center text-sm text-[#007AFF] hover:text-[#0056CC] mb-4"
      >
        <ArrowLeft class="w-4 h-4 mr-1" />
        Otras rutas
      </Link>

      <div class="mb-6">
        <p class="text-[11px] tracking-[0.22em] uppercase text-[#007AFF]">Sumni · {{ route.code }}</p>
        <h1 class="text-3xl font-display font-bold text-[#1D1D1F] mt-1">{{ route.name }}</h1>
        <p class="text-sm text-[#8E8E93] mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
          <span class="inline-flex items-center gap-1">
            <User class="w-3.5 h-3.5" />
            Prop: {{ route.owner_name || 'Sin propietario' }}
          </span>
          <span class="inline-flex items-center gap-1">
            <UserCog class="w-3.5 h-3.5" />
            Enc: {{ route.driver_name || 'Sin encargado' }}
          </span>
          <span v-if="route.vehicle_plate" class="inline-flex items-center gap-1">
            <Truck class="w-3.5 h-3.5" />
            {{ route.vehicle_description }} · {{ route.vehicle_plate }}
          </span>
          <span>Hoy {{ today }}</span>
        </p>
      </div>

      <div
        v-if="$page.props.flash?.success"
        class="bg-[#E8F8E8] border border-[#34C759] text-[#1D7A32] px-4 py-3 rounded-xl mb-4"
      >
        {{ $page.props.flash.success }}
      </div>
      <div
        v-if="$page.props.flash?.error"
        class="bg-[#FFE5E5] border border-[#FF3B30] text-[#FF3B30] px-4 py-3 rounded-xl mb-4"
      >
        {{ $page.props.flash.error }}
      </div>

      <div
        v-if="lastRecordedClient && !selected"
        class="bg-white rounded-2xl border border-[#007AFF]/30 p-4 mb-4 shadow-sm"
      >
        <p class="text-sm font-semibold text-[#1D1D1F]">
          Listo: {{ lastRecordedClient.full_name }} · {{ lastRecordedClient.today_liters }} L
        </p>
        <p class="text-xs text-[#8E8E93] mt-1">Entrega el baucher al productor.</p>
        <button
          type="button"
          @click="openLastRecordedVoucher"
          class="mt-3 w-full inline-flex items-center justify-center px-4 py-3 bg-[#007AFF] text-white rounded-2xl hover:bg-[#0056CC] text-sm font-semibold"
        >
          <Printer class="w-4 h-4 mr-2" />
          Imprimir baucher
        </button>
      </div>

      <div v-if="!selected" class="bg-white rounded-[28px] border border-[#E5E5E5] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E5E5E5] space-y-3">
          <div class="relative">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#8E8E93]" />
            <input
              type="text"
              v-model="searchQuery"
              autofocus
              placeholder="Buscar cliente pendiente o cédula..."
              class="w-full pl-12 pr-4 py-3.5 bg-[#F5F5F7] border-none rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-base text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
          <button
            type="button"
            @click="showNewClientModal = true"
            class="w-full inline-flex items-center justify-center px-4 py-3 border border-[#007AFF] text-[#007AFF] rounded-2xl hover:bg-[#E5F1FF] text-sm font-semibold"
          >
            <Plus class="w-4 h-4 mr-2" />
            Nuevo cliente en esta ruta
          </button>
        </div>

        <div v-if="filteredClients.length" class="divide-y divide-[#E5E5E5] max-h-[24rem] overflow-y-auto">
          <button
            v-for="client in filteredClients"
            :key="client.id"
            type="button"
            @click="openClient(client)"
            class="w-full text-left p-4 hover:bg-[#F5F5F7] transition-colors"
          >
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="font-semibold text-[#1D1D1F]">{{ client.full_name }}</p>
                <p v-if="client.identity_number" class="text-xs text-[#8E8E93] mt-0.5">{{ client.identity_number }}</p>
              </div>
              <span class="shrink-0 text-xs font-medium text-[#007AFF] bg-[#E5F1FF] px-3 py-1 rounded-full">
                Registrar litros
              </span>
            </div>
          </button>
        </div>
        <p v-else class="p-8 text-center text-[#8E8E93]">
          {{ searchQuery ? 'No hay clientes pendientes que coincidan.' : 'Todos los clientes de hoy ya están registrados.' }}
        </p>
      </div>

      <form v-else @submit.prevent="submit" class="bg-white rounded-[28px] border border-[#E5E5E5] shadow-sm p-6">
        <button
          type="button"
          @click="clearSelection"
          class="inline-flex items-center text-sm text-[#007AFF] hover:text-[#0056CC] mb-4"
        >
          <ArrowLeft class="w-4 h-4 mr-1" />
          Elegir otro cliente
        </button>

        <div class="flex items-center space-x-3 mb-6">
          <div class="w-12 h-12 bg-[#E5F1FF] rounded-2xl flex items-center justify-center">
            <Droplets class="w-6 h-6 text-[#007AFF]" />
          </div>
          <div>
            <p class="text-lg font-display font-bold text-[#1D1D1F]">{{ selected.full_name }}</p>
            <p class="text-sm text-[#8E8E93]">{{ route.code }} — {{ route.name }}</p>
          </div>
        </div>

        <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Litros recolectados hoy</label>
        <input
          type="number"
          min="0.1"
          step="0.1"
          v-model="form.liters"
          required
          autofocus
          placeholder="0.0"
          class="w-full bg-[#F5F5F7] border-none rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-4 px-4 text-3xl font-display font-bold text-center text-[#1D1D1F] placeholder-[#C7C7CC]"
        />
        <p v-if="form.errors.liters" class="text-xs text-[#FF3B30] mt-2 text-center">{{ form.errors.liters }}</p>

        <label class="block text-sm font-medium text-[#1D1D1F] mb-2 mt-5">Densidad de la leche (°C)</label>
        <input
          type="number"
          min="0"
          max="50"
          step="0.1"
          v-model="form.temperature"
          required
          placeholder="25.0"
          class="w-full bg-[#F5F5F7] border-none rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-4 text-xl font-display font-bold text-center text-[#1D1D1F] placeholder-[#C7C7CC]"
        />
        <p v-if="form.errors.temperature" class="text-xs text-[#FF3B30] mt-2 text-center">{{ form.errors.temperature }}</p>
        <p
          v-else-if="densityHint"
          class="text-xs mt-2 text-center font-medium"
          :class="{
            'text-[#34C759]': densityHint.tone === 'good',
            'text-[#FF9500]': densityHint.tone === 'warning',
            'text-[#FF3B30]': densityHint.tone === 'danger',
          }"
        >
          {{ densityHint.text }}
        </p>
        <p class="text-xs text-[#8E8E93] mt-2 text-center">
          Referencia: 25 °C buena leche · menos agua · más tiende a cortarse.
        </p>

        <p class="text-xs text-[#8E8E93] mt-4 text-center">
          Este registro queda fijo por hoy. No podrás editarlo después.
        </p>

        <button
          type="submit"
          :disabled="form.processing"
          class="mt-6 w-full inline-flex items-center justify-center px-5 py-3.5 bg-[#007AFF] text-white rounded-2xl hover:bg-[#0056CC] disabled:opacity-50 text-base font-semibold"
        >
          <Check v-if="!form.processing" class="w-5 h-5 mr-2" />
          {{ form.processing ? 'Guardando...' : 'Guardar acopio de hoy' }}
        </button>
      </form>

      <div v-if="recordedToday.length" class="mt-6">
        <p class="text-xs uppercase tracking-wider text-[#8E8E93] mb-2 flex items-center gap-1.5">
          <Lock class="w-3.5 h-3.5" />
          Registrados hoy (sin edición)
        </p>
        <div class="bg-white rounded-2xl border border-[#E5E5E5] divide-y divide-[#E5E5E5]">
          <div
            v-for="client in recordedToday"
            :key="`today-${client.id}`"
            class="px-4 py-3 flex items-center justify-between gap-3"
          >
            <div class="min-w-0">
              <p class="text-sm text-[#1D1D1F]">{{ client.full_name }}</p>
              <p class="text-xs text-[#8E8E93]">Registro del día</p>
            </div>
            <div class="text-right shrink-0">
              <p class="text-sm font-semibold text-[#34C759]">{{ client.today_liters }} L</p>
              <p class="text-xs font-medium" :class="densityBadge(client.today_density).class">
                {{ densityBadge(client.today_density).label }}
              </p>
              <button
                type="button"
                @click="openVoucher(client)"
                class="mt-1.5 inline-flex items-center text-xs font-semibold text-[#007AFF] hover:text-[#0056CC]"
              >
                <Printer class="w-3.5 h-3.5 mr-1" />
                Imprimir
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <SumniProducerModal
      :show="showNewClientModal"
      :route-id="route.id"
      @close="showNewClientModal = false"
    />

    <SumniVoucherModal
      :show="showVoucher"
      :voucher="voucher"
      @close="showVoucher = false"
    />
  </AppShell>
</template>
