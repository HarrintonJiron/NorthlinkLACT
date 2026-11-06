<script setup>
import AppShell from '../../../Components/AppShell.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, CheckCircle2, MapPin, Milk, Smartphone, UserRound } from '@lucide/vue'

const props = defineProps({ collection: Object })

const form = useForm({
  liters: props.collection.liters,
  density: props.collection.density ?? '',
  notes: props.collection.notes ?? '',
  resolution_notes: '',
})

const resolveCorrection = () => {
  form.patch(`/collections/${props.collection.id}/correction`, { preserveScroll: true })
}

const statusLabel = (status) => ({ requested: 'Corrección solicitada', resolved: 'Corrección resuelta' }[status] || 'Sin solicitud')
</script>

<template>
  <AppShell>
    <div class="mx-auto max-w-5xl space-y-5">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <Link href="/collections" class="mb-2 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700">
            <ArrowLeft class="h-4 w-4" /> Volver a recolecciones
          </Link>
          <h1 class="text-2xl font-bold text-slate-950">Detalle del acopio</h1>
          <p class="text-sm text-slate-500">Registro #{{ collection.id }} · {{ collection.collection_date }}</p>
        </div>
        <span class="rounded-full px-3 py-1.5 text-sm font-bold" :class="collection.correction_status === 'requested' ? 'bg-rose-100 text-rose-700' : collection.correction_status === 'resolved' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
          {{ statusLabel(collection.correction_status) }}
        </span>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4"><UserRound class="h-5 w-5 text-emerald-700" /><p class="mt-3 text-xs font-bold uppercase text-slate-400">Productor</p><p class="font-bold text-slate-950">{{ collection.producer?.full_name }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4"><MapPin class="h-5 w-5 text-emerald-700" /><p class="mt-3 text-xs font-bold uppercase text-slate-400">Ruta</p><p class="font-bold text-slate-950">{{ collection.route?.name }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4"><Milk class="h-5 w-5 text-emerald-700" /><p class="mt-3 text-xs font-bold uppercase text-slate-400">Cantidad</p><p class="font-bold text-slate-950">{{ collection.liters }} L · densidad {{ collection.density ?? '—' }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4"><Smartphone class="h-5 w-5 text-emerald-700" /><p class="mt-3 text-xs font-bold uppercase text-slate-400">Origen</p><p class="font-bold text-slate-950">{{ collection.device?.name || 'Plataforma web' }}</p></div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <h2 class="font-bold text-slate-950">Observaciones desde campo</h2>
        <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ collection.notes || 'Sin observaciones.' }}</p>
        <div v-if="collection.correction_reason" class="mt-4 rounded-xl bg-rose-50 p-4 text-sm text-rose-900">
          <p class="font-bold">Motivo informado por el rutero</p>
          <p class="mt-1">{{ collection.correction_reason }}</p>
        </div>
      </div>

      <form v-if="collection.correction_status === 'requested'" class="rounded-2xl border border-amber-200 bg-amber-50 p-5" @submit.prevent="resolveCorrection">
        <h2 class="font-bold text-slate-950">Revisar y corregir el registro</h2>
        <p class="mt-1 text-sm text-slate-600">La versión anterior quedará conservada en auditoría. Una planilla cerrada no se puede sobrescribir.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <label class="text-sm font-semibold text-slate-700">Litros corregidos<input v-model="form.liters" type="number" min="0.01" step="0.01" class="mt-1 w-full rounded-xl border-slate-300 bg-white" /></label>
          <label class="text-sm font-semibold text-slate-700">Densidad<input v-model="form.density" type="number" step="0.001" class="mt-1 w-full rounded-xl border-slate-300 bg-white" /></label>
          <label class="text-sm font-semibold text-slate-700 md:col-span-2">Observaciones del acopio<textarea v-model="form.notes" rows="3" class="mt-1 w-full rounded-xl border-slate-300 bg-white" /></label>
          <label class="text-sm font-semibold text-slate-700 md:col-span-2">Explicación de la corrección *<textarea v-model="form.resolution_notes" rows="3" required class="mt-1 w-full rounded-xl border-slate-300 bg-white" /></label>
        </div>
        <p v-if="form.errors.correction || form.errors.resolution_notes" class="mt-3 text-sm font-semibold text-rose-700">{{ form.errors.correction || form.errors.resolution_notes }}</p>
        <button :disabled="form.processing" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 font-bold text-white disabled:opacity-50"><CheckCircle2 class="h-4 w-4" /> Guardar corrección</button>
      </form>

      <div v-else-if="collection.correction_status === 'resolved'" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
        <p class="font-bold text-emerald-900">Corrección resuelta</p>
        <p class="mt-1 text-sm text-emerald-800">{{ collection.correction_resolution_notes }}</p>
      </div>
    </div>
  </AppShell>
</template>
