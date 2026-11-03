<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import FieldAppShell from '../../Components/FieldAppShell.vue'
import SumniProducerModal from '../../Components/SumniProducerModal.vue'
import SumniVoucherModal from '../../Components/SumniVoucherModal.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Search, Droplets, Check, ArrowLeft, User, Truck, UserCog, Plus, Lock, Printer, Wifi, WifiOff, RefreshCw, CloudUpload, Download, ShieldCheck } from '@lucide/vue'
import {
  enqueueCollection,
  getRouteCollections,
  isRouteReadyOffline,
  prepareRouteForOffline,
  saveRouteSnapshot,
  syncRouteCollections,
} from '../../offline/sumniOffline'

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
const localCollections = ref([])
const isOnline = ref(navigator.onLine)
const isSyncing = ref(false)
const isSaving = ref(false)
const syncMessage = ref('')
const lastSyncAt = ref(null)
const offlineReady = ref(isRouteReadyOffline(props.route.id, props.today))
const isPreparingOffline = ref(false)
const maxVisibleItems = 30

const form = useForm({
  producer_id: '',
  liters: '',
  temperature: '25',
})

const clientsWithLocalState = computed(() => {
  const localByProducer = new Map(
    localCollections.value.map((collection) => [collection.producerId, collection])
  )

  return (props.clients || []).map((client) => {
    const local = localByProducer.get(client.id)

    if (!local || client.recorded_today) {
      return client
    }

    return {
      ...client,
      recorded_today: true,
      today_liters: local.liters,
      today_density: local.temperature,
      local_sync_status: local.syncStatus,
    }
  })
})

const pendingClients = computed(() =>
  clientsWithLocalState.value.filter((client) => !client.recorded_today)
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

const visibleClients = computed(() => filteredClients.value.slice(0, maxVisibleItems))

const recordedToday = computed(() =>
  clientsWithLocalState.value.filter((client) => client.recorded_today)
)

const pendingSyncCount = computed(() =>
  localCollections.value.filter(({ syncStatus }) => syncStatus === 'pending' || syncStatus === 'failed').length
)

const conflictCount = computed(() =>
  localCollections.value.filter(({ syncStatus }) => syncStatus === 'conflict').length
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

const refreshLocalCollections = async () => {
  localCollections.value = await getRouteCollections(props.route.id, props.today)
}

const syncPending = async () => {
  if (!navigator.onLine || isSyncing.value || pendingSyncCount.value === 0) return

  isSyncing.value = true
  syncMessage.value = 'Sincronizando acopios...'

  try {
    const result = await syncRouteCollections({
      routeId: props.route.id,
      runDate: props.today,
      endpoint: `/sumni/${props.route.id}/offline-sync`,
      csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
    })

    await refreshLocalCollections()
    lastSyncAt.value = new Date()
    syncMessage.value = result.synced
      ? `${result.synced} acopio${result.synced === 1 ? '' : 's'} sincronizado${result.synced === 1 ? '' : 's'}.`
      : 'Todo está sincronizado.'
  } catch (error) {
    await refreshLocalCollections()
    syncMessage.value = error.message || 'No se pudo sincronizar. Se reintentará al recuperar internet.'
  } finally {
    isSyncing.value = false
  }
}

const handleOnline = () => {
  isOnline.value = true
  syncPending()
}

const handleOffline = () => {
  isOnline.value = false
  syncMessage.value = 'Sin internet. Los acopios se guardarán en este equipo.'
}

const prepareOffline = async () => {
  if (!navigator.onLine || isPreparingOffline.value) return

  isPreparingOffline.value = true
  syncMessage.value = 'Descargando ruta e interfaz para la jornada...'

  try {
    const result = await prepareRouteForOffline({ routeId: props.route.id, today: props.today })
    offlineReady.value = true
    syncMessage.value = result.persistent
      ? `Jornada offline lista · ${result.resources} archivos protegidos en el equipo.`
      : `Jornada offline lista · ${result.resources} archivos guardados. No borres los datos de Firefox.`
  } catch (error) {
    syncMessage.value = error.message || 'No se pudo preparar la jornada offline.'
  } finally {
    isPreparingOffline.value = false
  }
}

onMounted(async () => {
  await saveRouteSnapshot({ route: props.route, clients: props.clients, today: props.today })
  await refreshLocalCollections()
  autoSelectProducer()
  if (lastRecordedClient.value) {
    openVoucher(lastRecordedClient.value)
  }

  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
  window.addEventListener('focus', syncPending)

  if (isOnline.value) {
    syncPending()
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
  window.removeEventListener('focus', syncPending)
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

const submit = async () => {
  if (!selected.value || isSaving.value) return

  isSaving.value = true
  form.clearErrors()

  try {
    const collection = await enqueueCollection({
      routeId: props.route.id,
      collectionDate: props.today,
      producerId: selected.value.id,
      liters: form.liters,
      temperature: form.temperature,
    })

    const recordedClient = {
      ...selected.value,
      today_liters: collection.liters,
      today_density: collection.temperature,
    }

    await refreshLocalCollections()
    openVoucher(recordedClient)
    selected.value = null
    form.reset()
    searchQuery.value = ''
    syncMessage.value = isOnline.value
      ? 'Acopio guardado. Preparando sincronización...'
      : 'Acopio guardado en el equipo. Se enviará cuando regrese internet.'

    if (isOnline.value) {
      await syncPending()
    }
  } catch (error) {
    form.setError('liters', 'No se pudo guardar en el dispositivo. No continúes hasta reintentarlo.')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <FieldAppShell :show-back-to-routes="true">
    <div class="mx-auto w-full max-w-xl">
      <Link
        href="/sumni"
        class="mb-3 inline-flex min-h-11 items-center rounded-xl px-1 text-sm font-semibold text-[#176B45] sm:mb-4"
      >
        <ArrowLeft class="w-4 h-4 mr-1" />
        Otras rutas
      </Link>

      <div class="mb-4 px-1 sm:mb-6">
        <p class="text-[11px] tracking-[0.22em] uppercase text-[#007AFF]">Sumni · {{ route.code }}</p>
        <h1 class="mt-1 text-2xl font-extrabold leading-tight text-[#17221C] sm:text-3xl">{{ route.name }}</h1>
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

      <section
        class="mb-4 rounded-2xl border p-4"
        :class="isOnline ? 'border-[#34C759]/40 bg-[#E8F8E8]' : 'border-[#FF9500]/40 bg-[#FFF4E5]'"
        aria-live="polite"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-start gap-3 min-w-0">
            <Wifi v-if="isOnline" class="w-5 h-5 mt-0.5 shrink-0 text-[#1D7A32]" />
            <WifiOff v-else class="w-5 h-5 mt-0.5 shrink-0 text-[#B35A00]" />
            <div class="min-w-0">
              <p class="font-semibold" :class="isOnline ? 'text-[#1D7A32]' : 'text-[#8A4500]'">
                {{ isOnline ? 'Con conexión' : 'Trabajando sin internet' }}
              </p>
              <p class="text-xs mt-0.5 text-[#5E5E63]">
                {{ pendingSyncCount }} pendiente{{ pendingSyncCount === 1 ? '' : 's' }}
                <span v-if="conflictCount"> · {{ conflictCount }} con conflicto</span>
                <span v-if="lastSyncAt"> · Última carga {{ lastSyncAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) }}</span>
              </p>
              <p v-if="syncMessage" class="text-xs mt-1 text-[#5E5E63]">{{ syncMessage }}</p>
            </div>
          </div>
          <button
            type="button"
            :disabled="!isOnline || isSyncing || pendingSyncCount === 0"
            @click="syncPending"
            class="shrink-0 inline-flex items-center px-3 py-2 bg-white border border-black/10 rounded-xl text-xs font-semibold text-[#007AFF] disabled:text-[#8E8E93] disabled:opacity-60"
          >
            <RefreshCw class="w-4 h-4 mr-1.5" :class="{ 'animate-spin': isSyncing }" />
            {{ isSyncing ? 'Cargando' : 'Sincronizar' }}
          </button>
        </div>
      </section>

      <section class="mb-4 rounded-2xl border border-[#DCE5DF] bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between gap-3">
          <div class="flex min-w-0 items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl" :class="offlineReady ? 'bg-[#EAF5EE] text-[#176B45]' : 'bg-[#FFF4E5] text-[#B35A00]'">
              <ShieldCheck v-if="offlineReady" class="size-5" />
              <Download v-else class="size-5" />
            </span>
            <div class="min-w-0">
              <p class="text-sm font-bold text-[#17221C]">{{ offlineReady ? 'Jornada lista sin conexión' : 'Prepara la jornada antes de salir' }}</p>
              <p class="mt-0.5 text-xs leading-snug text-[#688075]">
                {{ offlineReady ? 'Puedes cerrar Firefox o reiniciar el equipo y continuar con esta ruta.' : 'Descarga la interfaz y la lista de productores mientras tienes internet.' }}
              </p>
            </div>
          </div>
        </div>
        <button
          v-if="!offlineReady"
          type="button"
          :disabled="!isOnline || isPreparingOffline"
          class="mt-3 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-[#176B45] px-4 text-sm font-bold text-white disabled:bg-[#AAB7B0]"
          @click="prepareOffline"
        >
          <Download class="mr-2 size-4" />
          {{ isPreparingOffline ? 'Descargando...' : 'Preparar jornada offline' }}
        </button>
      </section>

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

      <div v-if="!selected" class="overflow-hidden rounded-2xl border border-[#DCE5DF] bg-white shadow-sm sm:rounded-[28px]">
        <div class="space-y-3 border-b border-[#E3EAE6] p-3 sm:p-4">
          <div class="relative">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#8E8E93]" />
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Buscar cliente pendiente o cédula..."
              class="w-full pl-12 pr-4 py-3.5 bg-[#F5F5F7] border-none rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-base text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
          <button
            type="button"
            :disabled="!isOnline"
            @click="showNewClientModal = true"
            class="w-full inline-flex items-center justify-center px-4 py-3 border border-[#007AFF] text-[#007AFF] rounded-2xl hover:bg-[#E5F1FF] disabled:border-[#C7C7CC] disabled:text-[#8E8E93] disabled:bg-[#F5F5F7] text-sm font-semibold"
          >
            <Plus class="w-4 h-4 mr-2" />
            {{ isOnline ? 'Nuevo cliente en esta ruta' : 'Nuevo cliente requiere conexión' }}
          </button>
        </div>

        <div v-if="filteredClients.length" class="border-b border-[#E5E5E5] px-4 py-2 text-xs text-[#8E8E93]">
          Mostrando {{ visibleClients.length }} de {{ filteredClients.length }} clientes pendientes
        </div>

        <div v-if="filteredClients.length" class="divide-y divide-[#E3EAE6] sm:max-h-[26rem] sm:overflow-y-auto">
          <button
            v-for="client in visibleClients"
            :key="client.id"
            type="button"
            @click="openClient(client)"
            class="min-h-18 w-full p-4 text-left active:bg-[#EEF5F1] sm:hover:bg-[#F4F7F5]"
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

      <form v-else @submit.prevent="submit" class="rounded-2xl border border-[#DCE5DF] bg-white p-4 shadow-sm sm:rounded-[28px] sm:p-6">
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
          :disabled="isSaving"
          class="mt-6 inline-flex min-h-14 w-full items-center justify-center rounded-2xl bg-[#176B45] px-5 py-3.5 text-base font-bold text-white active:bg-[#125738] disabled:opacity-50"
        >
          <Check v-if="!isSaving" class="w-5 h-5 mr-2" />
          {{ isSaving ? 'Guardando...' : 'Guardar acopio de hoy' }}
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
              <p
                v-if="client.local_sync_status"
                class="text-[11px] mt-0.5 inline-flex items-center gap-1"
                :class="client.local_sync_status === 'synced' ? 'text-[#34C759]' : client.local_sync_status === 'conflict' ? 'text-[#FF3B30]' : 'text-[#FF9500]'"
              >
                <CloudUpload class="w-3 h-3" />
                {{ client.local_sync_status === 'synced' ? 'Sincronizado' : client.local_sync_status === 'conflict' ? 'Conflicto' : 'Pendiente de carga' }}
              </p>
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
  </FieldAppShell>
</template>
