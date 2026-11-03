<script setup>
import AppShell from '../../Components/AppShell.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'
import { Factory, Milk, PackageCheck, Play, Plus, Scale, ShieldCheck } from '@lucide/vue'

const props = defineProps({
  batches: { type: Array, required: true },
  stats: { type: Object, required: true },
  inventoryProducts: { type: Array, default: () => [] },
})

const showCreate = ref(false)
const expanded = ref(null)
const createForm = useForm({ source_date: new Date().toISOString().slice(0, 10), liters: '', notes: '' })
const inputForms = reactive({})
const weightForms = reactive({})
const closeForms = reactive({})
const costForms = reactive({})
const outputForms = reactive({})

const statusMeta = {
  planned: ['Planificado', 'bg-slate-100 text-slate-700'],
  in_process: ['En proceso', 'bg-blue-50 text-blue-700'],
  weighing: ['En pesaje', 'bg-amber-50 text-amber-700'],
  closed: ['Cerrado', 'bg-violet-50 text-violet-700'],
  released: ['Liberado', 'bg-emerald-50 text-emerald-700'],
}

const totals = computed(() => ({
  liters: props.batches.reduce((sum, batch) => sum + Number(batch.metrics.input_liters || 0), 0),
  cheese: props.batches.reduce((sum, batch) => sum + Number(batch.metrics.cheese_weight_lb || 0), 0),
}))

const number = (value, decimals = 2) => Number(value || 0).toLocaleString('es-NI', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })
const dateTimeLocal = () => {
  const date = new Date()
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}

const inputForm = (batch) => inputForms[batch.id] ??= { source_date: new Date().toISOString().slice(0, 10), liters: '' }
const weightForm = (batch) => weightForms[batch.id] ??= { weight_lb: '', scale_reference: '', weighed_at: dateTimeLocal() }
const closeForm = (batch) => closeForms[batch.id] ??= { cream_weight_lb: '', trimmings_weight_lb: '', reprocess_weight_lb: '', waste_weight_lb: '' }
const costForm = (batch) => costForms[batch.id] ??= { category: 'milk', description: '', amount: '' }
const outputForm = (batch) => outputForms[batch.id] ??= { inventory_product_id: '', quantity: '', expiration_date: '' }

const createBatch = () => createForm.post('/production', { onSuccess: () => { createForm.reset('liters', 'notes'); showCreate.value = false } })
const startBatch = (batch) => router.patch(`/production/${batch.id}/status`, { status: 'in_process' }, { preserveScroll: true })
const addInput = (batch) => router.post(`/production/${batch.id}/milk-inputs`, inputForm(batch), { preserveScroll: true, onSuccess: () => { inputForm(batch).liters = '' } })
const addWeight = (batch) => router.post(`/production/${batch.id}/weights`, weightForm(batch), { preserveScroll: true, onSuccess: () => { weightForm(batch).weight_lb = ''; weightForm(batch).weighed_at = dateTimeLocal() } })
const closeBatch = (batch) => router.patch(`/production/${batch.id}/status`, { status: 'closed', ...closeForm(batch) }, { preserveScroll: true })
const releaseBatch = (batch) => router.patch(`/production/${batch.id}/status`, { status: 'released' }, { preserveScroll: true })
const addCost = (batch) => router.post(`/production/${batch.id}/costs`, costForm(batch), { preserveScroll: true, onSuccess: () => { costForm(batch).description = ''; costForm(batch).amount = '' } })
const addFinishedLot = (batch) => router.post(`/production/${batch.id}/finished-lots`, outputForm(batch), { preserveScroll: true, onSuccess: () => { outputForm(batch).quantity = ''; outputForm(batch).expiration_date = '' } })
const correctWeight = (batch, entry) => {
  const weight = window.prompt(`Nuevo peso para el pesaje #${entry.sequence}:`, entry.weight_lb)
  if (!weight) return
  const reason = window.prompt('Motivo de la corrección (mínimo 5 caracteres):')
  if (!reason) return
  router.post(`/production/${batch.id}/weights/${entry.id}/correct`, { weight_lb: weight, reason }, { preserveScroll: true })
}
</script>

<template>
  <AppShell>
    <Head title="Producción" />
    <div class="mx-auto max-w-7xl space-y-5">
      <header class="flex flex-col gap-4 rounded-3xl bg-slate-950 p-6 text-white sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-300">Planta de quesos</p><h1 class="mt-2 font-display text-3xl font-bold">Producción y rendimiento</h1><p class="mt-2 text-sm text-slate-300">Lotes, entradas de leche, pesajes y rendimiento administrativo.</p></div>
        <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-bold hover:bg-blue-500" @click="showCreate = !showCreate"><Plus class="size-4" /> Nuevo lote</button>
      </header>

      <div v-if="$page.props.flash?.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ $page.props.flash.success }}</div>

      <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4"><span class="text-xs font-semibold text-slate-500">Lotes totales</span><strong class="mt-1 block text-2xl">{{ stats.total }}</strong></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4"><span class="text-xs font-semibold text-slate-500">Lotes abiertos</span><strong class="mt-1 block text-2xl text-blue-700">{{ stats.open }}</strong></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4"><span class="text-xs font-semibold text-slate-500">Litros registrados</span><strong class="mt-1 block text-2xl">{{ number(totals.liters, 1) }}</strong></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4"><span class="text-xs font-semibold text-slate-500">Queso pesado (lb)</span><strong class="mt-1 block text-2xl text-emerald-700">{{ number(totals.cheese, 1) }}</strong></div>
      </section>

      <form v-if="showCreate" class="grid gap-3 rounded-2xl border border-blue-200 bg-white p-5 sm:grid-cols-3" @submit.prevent="createBatch">
        <label class="text-sm font-semibold">Fecha de la leche<input v-model="createForm.source_date" type="date" class="mt-1 min-h-11 w-full rounded-xl border-slate-200"></label>
        <label class="text-sm font-semibold">Litros procesados<input v-model="createForm.liters" type="number" min="0.001" step="0.001" class="mt-1 min-h-11 w-full rounded-xl border-slate-200" required></label>
        <label class="text-sm font-semibold">Observaciones<input v-model="createForm.notes" class="mt-1 min-h-11 w-full rounded-xl border-slate-200"></label>
        <button class="min-h-11 rounded-xl bg-blue-600 px-5 font-bold text-white sm:col-span-3" :disabled="createForm.processing">Crear lote planificado</button>
      </form>

      <section class="space-y-3">
        <article v-for="batch in batches" :key="batch.id" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <button class="flex w-full flex-col gap-4 p-5 text-left sm:flex-row sm:items-center sm:justify-between" @click="expanded = expanded === batch.id ? null : batch.id">
            <div class="flex items-center gap-3"><div class="flex size-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700"><Factory class="size-5" /></div><div><h2 class="font-bold text-slate-950">{{ batch.batch_number }}</h2><p class="text-xs text-slate-500">{{ batch.plant.name }} · {{ batch.metrics.input_liters }} L</p></div></div>
            <div class="flex flex-wrap items-center gap-2"><span :class="['rounded-full px-2.5 py-1 text-xs font-bold', statusMeta[batch.status]?.[1]]">{{ statusMeta[batch.status]?.[0] }}</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs">Queso: {{ number(batch.metrics.cheese_weight_lb, 2) }} lb</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs">Rend.: {{ number(batch.metrics.cheese_yield_lb_per_gallon, 4) }} lb/gal</span></div>
          </button>

          <div v-if="expanded === batch.id" class="border-t border-slate-200 p-5">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <div class="rounded-xl bg-slate-50 p-3"><Milk class="size-4 text-blue-600" /><span class="mt-2 block text-xs text-slate-500">Galones equivalentes</span><strong>{{ number(batch.metrics.equivalent_gallons, 3) }}</strong></div>
              <div class="rounded-xl bg-slate-50 p-3"><Scale class="size-4 text-amber-600" /><span class="mt-2 block text-xs text-slate-500">Queso</span><strong>{{ number(batch.metrics.cheese_weight_lb, 3) }} lb</strong></div>
              <div class="rounded-xl bg-slate-50 p-3"><PackageCheck class="size-4 text-violet-600" /><span class="mt-2 block text-xs text-slate-500">Producto total</span><strong>{{ number(batch.metrics.total_output_lb, 3) }} lb</strong></div>
              <div class="rounded-xl bg-slate-50 p-3"><ShieldCheck class="size-4 text-emerald-600" /><span class="mt-2 block text-xs text-slate-500">Rendimiento total</span><strong>{{ number(batch.metrics.total_yield_lb_per_gallon, 4) }} lb/gal</strong></div>
            </div>

            <div v-if="batch.status === 'planned'" class="mt-4"><button class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-bold text-white" @click="startBatch(batch)"><Play class="size-4" /> Iniciar proceso</button></div>

            <div v-if="['in_process', 'weighing'].includes(batch.status)" class="mt-5 grid gap-4 lg:grid-cols-2">
              <form class="rounded-xl border border-slate-200 p-4" @submit.prevent="addInput(batch)"><h3 class="font-bold">Agregar leche al lote</h3><div class="mt-3 grid grid-cols-2 gap-2"><input v-model="inputForm(batch).source_date" type="date" class="min-h-10 rounded-lg border-slate-200"><input v-model="inputForm(batch).liters" type="number" min="0.001" step="0.001" placeholder="Litros" class="min-h-10 rounded-lg border-slate-200" required></div><button class="mt-2 min-h-10 w-full rounded-lg bg-slate-900 text-sm font-bold text-white">Guardar entrada</button></form>
              <form class="rounded-xl border border-slate-200 p-4" @submit.prevent="addWeight(batch)"><h3 class="font-bold">Registrar pesaje de queso</h3><div class="mt-3 grid gap-2 sm:grid-cols-3"><input v-model="weightForm(batch).weight_lb" type="number" min="0.001" step="0.001" placeholder="Libras" class="min-h-10 rounded-lg border-slate-200" required><input v-model="weightForm(batch).scale_reference" placeholder="Báscula" class="min-h-10 rounded-lg border-slate-200"><input v-model="weightForm(batch).weighed_at" type="datetime-local" class="min-h-10 rounded-lg border-slate-200" required></div><button class="mt-2 min-h-10 w-full rounded-lg bg-amber-500 text-sm font-bold text-slate-950">Agregar pesaje</button></form>
            </div>

            <div v-if="batch.weight_entries.length" class="mt-4 overflow-hidden rounded-xl border border-slate-200">
              <div class="border-b border-slate-200 bg-slate-50 px-4 py-3"><h3 class="font-bold">Historial de pesajes</h3><p class="text-xs text-slate-500">Las correcciones conservan el registro original.</p></div>
              <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-2">#</th><th class="px-3 py-2">Peso</th><th class="px-3 py-2">Báscula</th><th class="px-3 py-2">Estado</th><th class="px-3 py-2">Motivo</th><th class="px-3 py-2 text-right">Acción</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="entry in batch.weight_entries" :key="entry.id"><td class="px-4 py-2">{{ entry.sequence }}</td><td class="px-3 py-2 font-semibold">{{ number(entry.weight_lb, 3) }} lb</td><td class="px-3 py-2">{{ entry.scale_reference || '—' }}</td><td class="px-3 py-2"><span :class="['rounded-full px-2 py-1 text-xs font-bold', entry.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500']">{{ entry.status === 'active' ? 'Vigente' : 'Corregido' }}</span></td><td class="px-3 py-2 text-xs text-slate-500">{{ entry.correction_reason || '—' }}</td><td class="px-3 py-2 text-right"><button v-if="entry.status === 'active' && ['in_process', 'weighing'].includes(batch.status)" class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800" @click="correctWeight(batch, entry)">Corregir</button></td></tr></tbody></table></div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
              <div class="rounded-xl border border-slate-200 p-4"><div class="flex items-center justify-between"><div><h3 class="font-bold">Costos del lote</h3><p class="text-xs text-slate-500">Acumulado: C$ {{ number(batch.total_cost, 2) }}</p></div></div><div v-if="batch.costs.length" class="mt-3 max-h-28 space-y-1 overflow-auto text-xs"><div v-for="cost in batch.costs" :key="cost.id" class="flex justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2"><span>{{ cost.description }}</span><strong>C$ {{ number(cost.amount, 2) }}</strong></div></div><form v-if="batch.status !== 'released'" class="mt-3 grid gap-2 sm:grid-cols-3" @submit.prevent="addCost(batch)"><select v-model="costForm(batch).category" class="min-h-10 rounded-lg border-slate-200"><option value="milk">Leche</option><option value="labor">Mano de obra</option><option value="supplies">Insumos</option><option value="energy">Energía</option><option value="transport">Transporte</option><option value="other">Otro</option></select><input v-model="costForm(batch).description" placeholder="Descripción" class="min-h-10 rounded-lg border-slate-200" required><input v-model="costForm(batch).amount" type="number" min="0.01" step="0.01" placeholder="Monto C$" class="min-h-10 rounded-lg border-slate-200" required><button class="min-h-10 rounded-lg bg-slate-900 text-sm font-bold text-white sm:col-span-3">Agregar costo</button></form></div>
              <div class="rounded-xl border border-emerald-200 p-4"><h3 class="font-bold">Producto terminado</h3><p class="text-xs text-slate-500">Cada registro crea una entrada formal al kardex.</p><div v-if="batch.finished_lots.length" class="mt-3 space-y-1 text-xs"><div v-for="lot in batch.finished_lots" :key="lot.id" class="flex justify-between rounded-lg bg-emerald-50 px-3 py-2"><span>{{ lot.lot_number }} · {{ lot.product?.name }}</span><strong>{{ number(lot.available_quantity, 3) }} disp.</strong></div></div><form v-if="batch.status === 'released'" class="mt-3 grid gap-2 sm:grid-cols-2" @submit.prevent="addFinishedLot(batch)"><select v-model="outputForm(batch).inventory_product_id" class="min-h-10 rounded-lg border-emerald-200" required><option value="" disabled>Producto de inventario</option><option v-for="product in inventoryProducts" :key="product.id" :value="product.id">{{ product.code }} · {{ product.name }} ({{ product.unit?.symbol }})</option></select><input v-model="outputForm(batch).quantity" type="number" min="0.001" step="0.001" placeholder="Cantidad" class="min-h-10 rounded-lg border-emerald-200" required><input v-model="outputForm(batch).expiration_date" type="date" class="min-h-10 rounded-lg border-emerald-200"><button class="min-h-10 rounded-lg bg-emerald-600 text-sm font-bold text-white">Ingresar al kardex</button></form></div>
            </div>

            <form v-if="['in_process', 'weighing'].includes(batch.status) && batch.weight_entries.length" class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-4" @submit.prevent="closeBatch(batch)"><h3 class="font-bold">Cerrar lote</h3><p class="mt-1 text-xs text-slate-600">Registra subproductos y merma. El lote quedará bloqueado para nuevas entradas.</p><div class="mt-3 grid grid-cols-2 gap-2 lg:grid-cols-4"><input v-model="closeForm(batch).cream_weight_lb" type="number" min="0" step="0.001" placeholder="Crema lb" class="min-h-10 rounded-lg border-violet-200"><input v-model="closeForm(batch).trimmings_weight_lb" type="number" min="0" step="0.001" placeholder="Orillas lb" class="min-h-10 rounded-lg border-violet-200"><input v-model="closeForm(batch).reprocess_weight_lb" type="number" min="0" step="0.001" placeholder="Reproceso lb" class="min-h-10 rounded-lg border-violet-200"><input v-model="closeForm(batch).waste_weight_lb" type="number" min="0" step="0.001" placeholder="Merma lb" class="min-h-10 rounded-lg border-violet-200"></div><button class="mt-3 min-h-10 rounded-lg bg-violet-700 px-5 text-sm font-bold text-white">Cerrar lote</button></form>
            <button v-if="batch.status === 'closed'" class="mt-4 min-h-10 rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white" @click="releaseBatch(batch)">Liberar producción</button>
          </div>
        </article>
        <div v-if="!batches.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">No hay lotes registrados. Crea el primer lote con la leche que ingresará a producción.</div>
      </section>
    </div>
  </AppShell>
</template>
