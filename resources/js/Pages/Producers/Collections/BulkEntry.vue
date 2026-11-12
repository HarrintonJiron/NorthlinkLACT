<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, CalendarDays, ChevronLeft, ChevronRight, Grid3X3, Save } from '@lucide/vue'
import AppShell from '../../../Components/AppShell.vue'

const props = defineProps({
  routes: { type: Array, default: () => [] },
  selectedRoute: { type: Object, default: null },
  weekStart: { type: String, required: true },
  days: { type: Array, default: () => [] },
  rows: { type: Array, default: () => [] },
})

const routeId = ref(props.selectedRoute?.id || '')
const weekStart = ref(props.weekStart)
const values = reactive({})
const form = useForm({ route_id: routeId.value, week_start: weekStart.value, entries: [] })

const cellKey = (producerId, date) => `${producerId}|${date}`
const cellValue = (producerId, date) => values[cellKey(producerId, date)] ?? ''
const setCellValue = (producerId, date, value) => { values[cellKey(producerId, date)] = value }
const filledEntries = computed(() => Object.entries(values)
  .filter(([, liters]) => liters !== '' && Number(liters) > 0)
  .map(([key, liters]) => {
    const [producerId, date] = key.split('|')
    return { producer_id: Number(producerId), date, liters: String(liters) }
  }))
const dayTotals = computed(() => Object.fromEntries(props.days.map((day) => [
  day.date,
  props.rows.reduce((sum, row) => sum + Number(cellValue(row.id, day.date) || row.cells[day.date]?.liters || 0), 0),
])))
const grandTotal = computed(() => Object.values(dayTotals.value).reduce((sum, value) => sum + value, 0))

const reload = () => router.get('/collections/bulk', {
  route_id: routeId.value || undefined,
  week_start: weekStart.value,
}, { preserveScroll: true })
const moveWeek = (days) => {
  const date = new Date(`${props.weekStart}T00:00:00Z`)
  date.setUTCDate(date.getUTCDate() + days)
  weekStart.value = date.toISOString().slice(0, 10)
  reload()
}
const submit = () => {
  if (!filledEntries.value.length) return
  if (!window.confirm(`¿Registrar ${filledEntries.value.length} acopios por un total de ${grandTotal.value.toLocaleString('es-NI')} litros?`)) return

  form.route_id = routeId.value
  form.week_start = props.weekStart
  form.entries = filledEntries.value
  form.post('/collections/bulk', { preserveScroll: true })
}
</script>

<template>
  <AppShell>
    <div class="flex flex-col gap-5">
      <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <Link href="/collections" class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-900"><ArrowLeft class="size-4" /> Volver a recolecciones</Link>
          <p class="text-sm font-semibold uppercase tracking-[0.16em] text-emerald-700">Carga administrativa</p>
          <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Ingreso masivo semanal</h1>
          <p class="mt-1 text-sm text-slate-600">Complete las celdas nuevas de la semana de pago, de viernes a jueves. Los registros existentes permanecen protegidos.</p>
        </div>
        <button type="button" :disabled="form.processing || !filledEntries.length" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 font-bold text-white shadow-sm transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50" @click="submit">
          <Save class="size-5" /> {{ form.processing ? 'Guardando…' : `Guardar ${filledEntries.length} registros` }}
        </button>
      </header>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-[minmax(16rem,1fr)_auto_minmax(12rem,auto)] lg:items-end">
          <label class="text-sm font-semibold text-slate-700">Ruta
            <select v-model="routeId" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-slate-900 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" @change="reload">
              <option value="" disabled>Seleccione una ruta</option>
              <option v-for="route in routes" :key="route.id" :value="route.id">{{ route.code }} · {{ route.name }}</option>
            </select>
          </label>
          <div class="flex items-center justify-center gap-2">
            <button type="button" class="grid size-11 place-items-center rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50" title="Semana anterior" @click="moveWeek(-7)"><ChevronLeft class="size-5" /></button>
            <label class="text-sm font-semibold text-slate-700">Semana de pago (viernes a jueves)
              <span class="relative mt-1 block"><CalendarDays class="absolute left-3 top-3.5 size-4 text-slate-400" /><input v-model="weekStart" type="date" class="min-h-11 rounded-xl border border-slate-300 pl-10 pr-3 text-slate-900 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" @change="reload"></span>
            </label>
            <button type="button" class="mt-6 grid size-11 place-items-center rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50" title="Semana siguiente" @click="moveWeek(7)"><ChevronRight class="size-5" /></button>
          </div>
          <div class="rounded-xl bg-emerald-50 px-4 py-3 text-right">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Total a registrar</p>
            <p class="text-2xl font-black text-emerald-950">{{ grandTotal.toLocaleString('es-NI') }} L</p>
          </div>
        </div>
      </section>

      <div v-if="form.hasErrors" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
        No se guardó ningún registro. Revise las celdas marcadas o recargue la semana para ver datos agregados recientemente.
      </div>

      <section v-if="selectedRoute && rows.length" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
          <div class="flex items-center gap-2"><Grid3X3 class="size-5 text-emerald-700" /><div><h2 class="font-bold text-slate-950">{{ selectedRoute.code }} · {{ selectedRoute.name }}</h2><p class="text-xs text-slate-500">{{ rows.length }} productores activos</p></div></div>
          <p class="text-xs text-slate-500"><span class="font-bold text-emerald-700">Verde:</span> existente · <span class="font-bold text-amber-700">Ámbar:</span> planilla cerrada</p>
        </div>
        <div class="max-h-[65vh] overflow-auto">
          <table class="min-w-[78rem] border-separate border-spacing-0 text-sm">
            <thead class="sticky top-0 z-20 bg-slate-900 text-white">
              <tr>
                <th class="sticky left-0 z-30 min-w-64 border-b border-r border-slate-700 bg-slate-900 px-4 py-3 text-left">Productor</th>
                <th v-for="day in days" :key="day.date" class="min-w-32 border-b border-r border-slate-700 px-3 py-3 text-center"><span class="block font-bold">{{ day.label }}</span><span class="text-[11px] font-normal text-slate-300">{{ day.date }}</span></th>
                <th class="min-w-32 border-b border-slate-700 px-3 py-3 text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.id" class="group odd:bg-white even:bg-slate-50/70 hover:bg-emerald-50/40">
                <th class="sticky left-0 z-10 border-b border-r border-slate-200 bg-inherit px-4 py-2 text-left"><span class="block font-bold text-slate-900">{{ row.full_name }}</span><span class="text-xs font-medium text-slate-500">{{ row.code }}</span></th>
                <td v-for="day in days" :key="day.date" class="border-b border-r border-slate-200 p-1.5">
                  <div v-if="row.cells[day.date]?.exists" class="rounded-lg bg-emerald-100 px-3 py-2 text-center font-black text-emerald-900" :title="row.cells[day.date].source === 'sunmi' ? 'Sincronizado desde SUNMI' : 'Registrado manualmente'">{{ Number(row.cells[day.date].liters).toLocaleString('es-NI') }} L</div>
                  <div v-else-if="row.cells[day.date]?.locked" class="rounded-lg bg-amber-100 px-3 py-2 text-center text-xs font-bold text-amber-900">Cerrado</div>
                  <div v-else-if="!row.cells[day.date]?.available" class="rounded-lg bg-slate-100 px-3 py-2 text-center text-xs font-bold text-slate-400">Fuera de ruta</div>
                  <input v-else :value="cellValue(row.id, day.date)" type="number" min="0" max="10000" step="0.01" inputmode="decimal" placeholder="Litros" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-2 text-right font-bold text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" @input="setCellValue(row.id, day.date, $event.target.value)">
                </td>
                <td class="border-b border-slate-200 px-3 py-2 text-right font-black text-slate-900">{{ days.reduce((sum, day) => sum + Number(cellValue(row.id, day.date) || row.cells[day.date]?.liters || 0), 0).toLocaleString('es-NI') }} L</td>
              </tr>
            </tbody>
            <tfoot class="sticky bottom-0 z-20 bg-slate-100 font-black text-slate-950">
              <tr><th class="sticky left-0 z-30 border-r border-t border-slate-300 bg-slate-100 px-4 py-3 text-left">Totales diarios</th><td v-for="day in days" :key="day.date" class="border-r border-t border-slate-300 px-3 py-3 text-right">{{ dayTotals[day.date].toLocaleString('es-NI') }} L</td><td class="border-t border-slate-300 px-3 py-3 text-right text-emerald-800">{{ grandTotal.toLocaleString('es-NI') }} L</td></tr>
            </tfoot>
          </table>
        </div>
      </section>
      <section v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"><Grid3X3 class="mx-auto size-10 text-slate-300" /><h2 class="mt-3 font-bold text-slate-900">No hay productores para mostrar</h2><p class="mt-1 text-sm text-slate-500">Seleccione una ruta con productores activos durante esta semana.</p></section>
    </div>
  </AppShell>
</template>
