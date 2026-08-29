<script setup>
import { computed, onMounted, ref } from 'vue'
import AppShell from '../../Components/AppShell.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Search, Droplets, Check, ArrowLeft, User, Truck } from '@lucide/vue'

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
})

const searchQuery = ref('')
const selected = ref(null)

const form = useForm({
  producer_id: '',
  liters: '',
})

const filteredClients = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const list = props.clients || []
  if (!query) return list

  return list.filter((client) =>
    [client.full_name, client.identity_number, client.phone, client.code]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(query))
  )
})

const recordedToday = computed(() =>
  (props.clients || []).filter((client) => client.today_liters != null)
)

const selectClient = (client) => {
  selected.value = client
  form.producer_id = client.id
  form.liters = client.today_liters ?? ''
  form.clearErrors()
}

const clearSelection = () => {
  selected.value = null
  form.reset()
  form.clearErrors()
}

onMounted(() => {
  if (form.producer_id) {
    selected.value = props.clients.find((client) => client.id == form.producer_id) || null
  }
})

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
            {{ route.owner_name || 'Sin propietario' }}
          </span>
          <span v-if="route.vehicle_plate" class="inline-flex items-center gap-1">
            <Truck class="w-3.5 h-3.5" />
            {{ route.vehicle_plate }}
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

      <div v-if="!selected" class="bg-white rounded-[28px] border border-[#E5E5E5] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E5E5E5]">
          <div class="relative">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#8E8E93]" />
            <input
              type="text"
              v-model="searchQuery"
              autofocus
              placeholder="Buscar cliente o cédula..."
              class="w-full pl-12 pr-4 py-3.5 bg-[#F5F5F7] border-none rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-base text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
        </div>

        <div v-if="filteredClients.length" class="divide-y divide-[#E5E5E5] max-h-[28rem] overflow-y-auto">
          <button
            v-for="client in filteredClients"
            :key="client.id"
            type="button"
            @click="selectClient(client)"
            class="w-full text-left p-4 hover:bg-[#F5F5F7] transition-colors"
          >
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="font-semibold text-[#1D1D1F]">{{ client.full_name }}</p>
                <p v-if="client.identity_number" class="text-xs text-[#8E8E93] mt-0.5">{{ client.identity_number }}</p>
              </div>
              <span
                v-if="client.today_liters != null"
                class="shrink-0 text-sm font-semibold text-[#34C759] bg-[#E8F8E8] px-3 py-1 rounded-full"
              >
                {{ client.today_liters }} L
              </span>
            </div>
          </button>
        </div>
        <p v-else class="p-8 text-center text-[#8E8E93]">
          {{ searchQuery ? 'No hay clientes que coincidan.' : 'Esta ruta no tiene clientes asignados.' }}
        </p>
      </div>

      <form v-else @submit.prevent="submit" class="bg-white rounded-[28px] border border-[#E5E5E5] shadow-sm p-6">
        <button
          type="button"
          @click="clearSelection"
          class="inline-flex items-center text-sm text-[#007AFF] hover:text-[#0056CC] mb-4"
        >
          <ArrowLeft class="w-4 h-4 mr-1" />
          Buscar otro cliente
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

        <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Litros recolectados</label>
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
        <p v-if="selected.today_liters != null" class="text-xs text-[#8E8E93] mt-2 text-center">
          Ya hay {{ selected.today_liters }} L hoy. Guardar reemplaza ese dato.
        </p>

        <button
          type="submit"
          :disabled="form.processing"
          class="mt-6 w-full inline-flex items-center justify-center px-5 py-3.5 bg-[#007AFF] text-white rounded-2xl hover:bg-[#0056CC] disabled:opacity-50 text-base font-semibold"
        >
          <Check v-if="!form.processing" class="w-5 h-5 mr-2" />
          {{ form.processing ? 'Guardando...' : 'Guardar litros' }}
        </button>
      </form>

      <div v-if="recordedToday.length && !selected" class="mt-6">
        <p class="text-xs uppercase tracking-wider text-[#8E8E93] mb-2">Registrados hoy en esta ruta</p>
        <div class="bg-white rounded-2xl border border-[#E5E5E5] divide-y divide-[#E5E5E5]">
          <div
            v-for="client in recordedToday"
            :key="`today-${client.id}`"
            class="px-4 py-3 flex items-center justify-between"
          >
            <p class="text-sm text-[#1D1D1F]">{{ client.full_name }}</p>
            <p class="text-sm font-semibold text-[#1D1D1F]">{{ client.today_liters }} L</p>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>
