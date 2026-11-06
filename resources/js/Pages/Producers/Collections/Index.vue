<script setup>
import { computed, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { AlertTriangle, CalendarDays, ChevronLeft, ChevronRight, CloudCheck, Droplets, Plus, RotateCcw, Search, Smartphone, Users } from '@lucide/vue'
import AppShell from '../../../Components/AppShell.vue'
import EmptyState from '../../../Components/EmptyState.vue'

const props = defineProps({
  collections: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  routes: { type: Array, default: () => [] },
  summary: { type: Object, required: true },
  routeProgress: { type: Array, default: () => [] },
  progressDate: String,
})

const form = reactive({
  search: props.filters.search || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  route_id: props.filters.route_id || '',
  visit_status: props.filters.visit_status || '',
  sync_status: props.filters.sync_status || '',
  source: props.filters.source || '',
})

const hasFilters = computed(() => Object.values(form).some(Boolean))
const applyFilters = () => router.get('/collections', form, { preserveState: true, preserveScroll: true, replace: true })
const clearFilters = () => {
  Object.keys(form).forEach((key) => { form[key] = '' })
  router.get('/collections', {}, { preserveState: true, replace: true })
}

const formatDate = (value) => {
  if (!value) return 'Sin fecha'

  const datePart = String(value).slice(0, 10)
  const date = new Date(`${datePart}T00:00:00Z`)

  return Number.isNaN(date.getTime())
    ? 'Fecha no disponible'
    : new Intl.DateTimeFormat('es-NI', { dateStyle: 'medium', timeZone: 'UTC' }).format(date)
}

const formatDateTime = (value) => value
  ? new Intl.DateTimeFormat('es-NI', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value))
  : 'Todavía no hay sincronizaciones'

const visitLabel = (status) => ({ entregado: 'Entregado', ausente: 'Ausente', rechazado: 'Rechazado', pendiente: 'Pendiente' }[status] || status)
const visitClass = (status) => ({
  entregado: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  ausente: 'bg-slate-100 text-slate-700 ring-slate-200',
  rechazado: 'bg-rose-50 text-rose-700 ring-rose-200',
  pendiente: 'bg-amber-50 text-amber-700 ring-amber-200',
}[status] || 'bg-slate-100 text-slate-700 ring-slate-200')
const collectionDensity = (collection) => collection.density ?? collection.temperature ?? null
</script>

<template>
  <AppShell>
    <div class="flex flex-col gap-5">
      <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.16em] text-emerald-700">Operación central</p>
          <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Acopio de leche</h1>
          <p class="mt-1 max-w-2xl text-sm text-slate-600">Consulta lo recibido en planta, identifica el origen y vigila la sincronización de los ruteros.</p>
        </div>
        <Link href="/collections/create" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-emerald-800">
          <Plus class="size-5" /> Registrar acopio
        </Link>
      </header>

      <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between"><span class="text-sm font-medium text-slate-500">Registros filtrados</span><Users class="size-5 text-slate-400" /></div>
          <p class="mt-3 text-3xl font-bold text-slate-950">{{ summary.total_records }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between"><span class="text-sm font-medium text-slate-500">Litros acumulados</span><Droplets class="size-5 text-sky-500" /></div>
          <p class="mt-3 text-3xl font-bold text-slate-950">{{ Number(summary.total_liters).toLocaleString('es-NI') }} <span class="text-base font-semibold text-slate-500">L</span></p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between"><span class="text-sm font-medium text-slate-500">Sincronizados</span><CloudCheck class="size-5 text-emerald-600" /></div>
          <p class="mt-3 text-3xl font-bold text-slate-950">{{ summary.synced_records }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ formatDateTime(summary.last_synced_at) }}</p>
        </article>
        <article class="rounded-2xl border p-4 shadow-sm" :class="summary.conflict_records > 0 ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-white'">
          <div class="flex items-center justify-between"><span class="text-sm font-medium" :class="summary.conflict_records > 0 ? 'text-rose-700' : 'text-slate-500'">Conflictos</span><AlertTriangle class="size-5" :class="summary.conflict_records > 0 ? 'text-rose-600' : 'text-slate-400'" /></div>
          <p class="mt-3 text-3xl font-bold" :class="summary.conflict_records > 0 ? 'text-rose-800' : 'text-slate-950'">{{ summary.conflict_records }}</p>
        </article>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div><h2 class="font-bold text-slate-950">Avance por ruta</h2><p class="text-xs text-slate-500">Estado operativo del {{ formatDate(progressDate) }}</p></div>
          <span class="text-xs font-semibold text-slate-500">{{ routeProgress.filter((route) => route.completed).length }} de {{ routeProgress.length }} terminadas</span>
        </div>
        <div v-if="routeProgress.length" class="mt-4 grid max-h-72 gap-3 overflow-y-auto md:grid-cols-2 xl:grid-cols-3">
          <article v-for="route in routeProgress" :key="route.id" class="rounded-xl border border-slate-200 p-3">
            <div class="flex items-start justify-between gap-2"><div><p class="text-xs font-semibold text-slate-400">{{ route.code }}</p><p class="font-bold text-slate-900">{{ route.name }}</p></div><span class="rounded-full px-2 py-1 text-xs font-bold" :class="route.completed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ route.completed ? 'Terminada' : `${route.pending} faltan` }}</span></div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full" :class="route.completed ? 'bg-emerald-600' : 'bg-amber-500'" :style="{ width: `${route.percent}%` }" /></div>
            <p class="mt-2 text-xs text-slate-500">{{ route.visited }}/{{ route.scheduled }} productores · {{ Number(route.liters).toLocaleString('es-NI') }} L</p>
          </article>
        </div>
        <p v-else class="mt-4 text-sm text-slate-500">No hay rutas activas visibles.</p>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form class="border-b border-slate-200 p-4" @submit.prevent="applyFilters">
          <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <label class="relative xl:col-span-2"><span class="sr-only">Buscar</span><Search class="absolute left-3 top-3.5 size-4 text-slate-400" /><input v-model="form.search" type="search" placeholder="Productor, código o ruta" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 text-sm text-slate-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"></label>
            <select v-model="form.route_id" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"><option value="">Todas las rutas</option><option v-for="routeItem in routes" :key="routeItem.id" :value="routeItem.id">{{ routeItem.code }} · {{ routeItem.name }}</option></select>
            <select v-model="form.source" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"><option value="">Cualquier origen</option><option value="offline">Sincronizado desde rutero</option><option value="manual">Registrado en plataforma</option></select>
            <label class="relative"><CalendarDays class="absolute left-3 top-3.5 size-4 text-slate-400" /><input v-model="form.date_from" type="date" aria-label="Fecha inicial" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 text-sm text-slate-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"></label>
            <label class="relative"><CalendarDays class="absolute left-3 top-3.5 size-4 text-slate-400" /><input v-model="form.date_to" type="date" aria-label="Fecha final" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 text-sm text-slate-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"></label>
            <select v-model="form.visit_status" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"><option value="">Todos los resultados</option><option value="entregado">Entregado</option><option value="ausente">Ausente</option><option value="rechazado">Rechazado</option><option value="pendiente">Pendiente</option></select>
            <select v-model="form.sync_status" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"><option value="">Cualquier sincronización</option><option value="synced">Sincronizado</option><option value="pending">Pendiente</option><option value="conflict">Conflicto</option></select>
          </div>
          <div class="mt-3 flex flex-wrap justify-end gap-2">
            <button v-if="hasFilters" type="button" class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="clearFilters"><RotateCcw class="size-4" /> Limpiar</button>
            <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-800"><Search class="size-4" /> Aplicar filtros</button>
          </div>
        </form>

        <div v-if="collections.data.length" class="max-h-[46rem] divide-y divide-slate-100 overflow-y-auto">
          <article v-for="collection in collections.data" :key="collection.id" class="grid gap-3 p-4 transition hover:bg-slate-50 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto] lg:items-center">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2"><Link :href="`/collections/${collection.id}`" class="truncate font-bold text-slate-950 hover:text-emerald-700">{{ collection.producer?.full_name }}</Link><span class="rounded-full px-2 py-1 text-xs font-semibold ring-1 ring-inset" :class="visitClass(collection.visit_status)">{{ visitLabel(collection.visit_status) }}</span><span v-if="collection.correction_status === 'requested'" class="rounded-full bg-rose-100 px-2 py-1 text-xs font-bold text-rose-700">Corrección solicitada</span></div>
              <p class="mt-1 text-sm text-slate-600">{{ collection.producer?.code }} · {{ collection.route?.code }} · {{ collection.route?.name }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ collection.plant?.name }} · {{ formatDate(collection.collection_date) }}</p>
              <p v-if="collection.correction_status === 'requested'" class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-xs font-medium text-rose-800">Motivo: {{ collection.correction_reason }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
              <div><p class="text-xs font-medium uppercase tracking-wide text-slate-400">Volumen</p><p class="font-bold text-slate-950">{{ Number(collection.liters).toLocaleString('es-NI') }} L</p></div>
              <div v-if="collectionDensity(collection) !== null"><p class="text-xs font-medium uppercase tracking-wide text-slate-400">Densidad</p><p class="font-semibold text-slate-800">{{ collectionDensity(collection) }}</p></div>
              <div><p class="text-xs font-medium uppercase tracking-wide text-slate-400">Origen</p><p class="inline-flex items-center gap-1 font-semibold text-slate-700"><Smartphone v-if="collection.route_run_id" class="size-4 text-emerald-600" />{{ collection.route_run_id ? 'Rutero offline' : 'Plataforma' }}</p></div>
            </div>
            <div class="flex items-center justify-between gap-3 lg:justify-end">
              <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="collection.sync_status === 'conflict' ? 'bg-rose-100 text-rose-700' : collection.sync_status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'">{{ collection.sync_status === 'conflict' ? 'Conflicto' : collection.sync_status === 'pending' ? 'Pendiente' : 'Sincronizado' }}</span>
              <Link :href="`/collections/${collection.id}`" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">Ver →</Link>
            </div>
          </article>
        </div>

        <EmptyState v-else title="No hay acopios con estos filtros" description="Cambia los filtros o registra el primer acopio del período." :icon="Droplets" action-label="Registrar acopio" action-href="/collections/create" />

        <footer v-if="collections.last_page > 1" class="flex flex-col gap-3 border-t border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-sm text-slate-500">Mostrando {{ collections.from }}–{{ collections.to }} de {{ collections.total }}</p>
          <div class="flex gap-2"><Link v-if="collections.prev_page_url" :href="collections.prev_page_url" preserve-scroll class="inline-flex min-h-10 items-center gap-1 rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"><ChevronLeft class="size-4" /> Anterior</Link><Link v-if="collections.next_page_url" :href="collections.next_page_url" preserve-scroll class="inline-flex min-h-10 items-center gap-1 rounded-xl border border-slate-300 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Siguiente <ChevronRight class="size-4" /></Link></div>
        </footer>
      </section>
    </div>
  </AppShell>
</template>
