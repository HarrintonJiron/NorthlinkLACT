<script setup>
import AppShell from '../../Components/AppShell.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Send, PackageCheck, MapPin, BadgeCheck } from '@lucide/vue'

const props = defineProps({ lots: { type: Array, default: () => [] }, dispatches: { type: Array, default: () => [] } })
const form = useForm({ dispatch_date: new Date().toISOString().slice(0, 10), destination: '', recipient: '', notes: '', items: [] })
const rows = computed(() => props.lots.map((lot) => ({ ...lot, requested: form.items.find((item) => item.lot_id === lot.id)?.quantity || '' })))
const setQuantity = (lot, value) => {
  const items = form.items.filter((item) => item.lot_id !== lot.id)
  if (Number(value) > 0) items.push({ lot_id: lot.id, quantity: value })
  form.items = items
}
const submit = () => form.post('/dispatches', { preserveScroll: true, onSuccess: () => form.reset('destination', 'recipient', 'notes', 'items') })
const receive = (dispatch) => router.patch(`/dispatches/${dispatch.id}/receive`, {}, { preserveScroll: true })
const qty = (value) => Number(value || 0).toLocaleString('es-NI', { maximumFractionDigits: 3 })
</script>

<template>
  <AppShell><Head title="Remisiones" /><div class="mx-auto max-w-7xl space-y-5">
    <header class="rounded-3xl bg-slate-950 p-6 text-white"><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-300">Producto terminado</p><h1 class="mt-2 text-3xl font-bold">Remisiones y despachos</h1><p class="mt-2 text-sm text-slate-300">Salida física por lote con descuento automático de existencias.</p></header>
    <div v-if="$page.props.flash?.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ $page.props.flash.success }}</div>
    <div v-if="Object.keys($page.props.errors || {}).length" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ Object.values($page.props.errors)[0] }}</div>

    <form class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="submit"><div class="flex items-center gap-3"><Send class="size-5 text-blue-700" /><div><h2 class="font-bold">Nueva remisión</h2><p class="text-xs text-slate-500">Solo aparecen lotes con disponibilidad.</p></div></div><div class="mt-4 grid gap-3 sm:grid-cols-3"><input v-model="form.dispatch_date" type="date" class="min-h-11 rounded-xl border-slate-200" required><input v-model="form.destination" placeholder="Destino" class="min-h-11 rounded-xl border-slate-200" required><input v-model="form.recipient" placeholder="Recibe" class="min-h-11 rounded-xl border-slate-200"><input v-model="form.notes" placeholder="Observaciones" class="min-h-11 rounded-xl border-slate-200 sm:col-span-3"></div><div class="mt-4 max-h-72 overflow-auto rounded-xl border border-slate-200"><table class="w-full text-sm"><thead class="sticky top-0 bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Lote</th><th class="px-3 py-3">Producto</th><th class="px-3 py-3 text-right">Disponible</th><th class="px-3 py-3 text-right">Enviar</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="lot in rows" :key="lot.id"><td class="px-4 py-3 font-semibold">{{ lot.lot_number }}</td><td class="px-3 py-3">{{ lot.product?.name }} <span class="text-xs text-slate-500">({{ lot.product?.unit?.symbol }})</span></td><td class="px-3 py-3 text-right">{{ qty(lot.available_quantity) }}</td><td class="px-3 py-3 text-right"><input type="number" min="0" :max="lot.available_quantity" step="0.001" :value="lot.requested" class="w-28 rounded-lg border-slate-200 text-right" @input="setQuantity(lot, $event.target.value)"></td></tr><tr v-if="!rows.length"><td colspan="4" class="px-4 py-8 text-center text-slate-500">No hay producto terminado disponible.</td></tr></tbody></table></div><button class="mt-4 min-h-11 rounded-xl bg-blue-600 px-5 font-bold text-white" :disabled="form.processing || !form.items.length">Crear y despachar remisión</button></form>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-200 p-5"><h2 class="font-bold">Últimas remisiones</h2></div><div class="max-h-[34rem] overflow-auto"><table class="w-full text-sm"><thead class="sticky top-0 bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Código</th><th class="px-3 py-3">Destino</th><th class="px-3 py-3">Detalle</th><th class="px-3 py-3">Estado</th><th class="px-3 py-3 text-right">Acción</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="dispatch in dispatches" :key="dispatch.id"><td class="px-4 py-3"><strong>{{ dispatch.code }}</strong><p class="text-xs text-slate-500">{{ String(dispatch.dispatch_date).slice(0, 10) }}</p></td><td class="px-3 py-3"><span class="inline-flex items-center gap-1"><MapPin class="size-3.5 text-blue-600" />{{ dispatch.destination }}</span><p class="text-xs text-slate-500">{{ dispatch.recipient || 'Sin receptor indicado' }}</p></td><td class="px-3 py-3"><p v-for="item in dispatch.items" :key="item.id" class="text-xs">{{ item.lot?.lot_number }} · {{ item.product?.name }}: {{ qty(item.quantity) }}</p></td><td class="px-3 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="dispatch.status === 'received' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'">{{ dispatch.status === 'received' ? 'Recibida' : 'Despachada' }}</span></td><td class="px-3 py-3 text-right"><button v-if="dispatch.status === 'dispatched'" class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700" @click="receive(dispatch)"><BadgeCheck class="size-4" />Confirmar recepción</button></td></tr><tr v-if="!dispatches.length"><td colspan="5" class="px-4 py-10 text-center text-slate-500">Todavía no hay remisiones.</td></tr></tbody></table></div></section>
  </div></AppShell>
</template>
