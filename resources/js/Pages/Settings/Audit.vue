<script setup>
import AppShell from '../../Components/AppShell.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import { History, Search } from '@lucide/vue'

const props = defineProps({ events: Object, filters: Object, actions: Array })
const form = reactive({ search: props.filters.search || '', action: props.filters.action || '', from: props.filters.from || '', to: props.filters.to || '' })
const apply = () => router.get('/settings/audit', form, { preserveState: true, replace: true })
const entity = (value) => String(value || '').split('\\').pop()
const date = (value) => new Intl.DateTimeFormat('es-NI', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value))
</script>

<template>
  <AppShell><Head title="Auditoría" /><div class="mx-auto max-w-7xl space-y-5">
    <header><h1 class="text-3xl font-bold">Auditoría del sistema</h1><p class="mt-1 text-sm text-slate-600">Historial consultable de operaciones críticas y cambios administrativos.</p></header>
    <form class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-5" @submit.prevent="apply">
      <label class="relative sm:col-span-2"><Search class="absolute left-3 top-3.5 size-4 text-slate-400" /><input v-model="form.search" placeholder="Buscar descripción o entidad" class="min-h-11 w-full rounded-xl border-slate-200 pl-9"></label>
      <select v-model="form.action" class="min-h-11 rounded-xl border-slate-200"><option value="">Todas las acciones</option><option v-for="action in actions" :key="action" :value="action">{{ action }}</option></select>
      <input v-model="form.from" type="date" class="min-h-11 rounded-xl border-slate-200"><input v-model="form.to" type="date" class="min-h-11 rounded-xl border-slate-200"><button class="min-h-10 rounded-xl bg-slate-950 font-bold text-white sm:col-span-5">Aplicar filtros</button>
    </form>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white"><div class="max-h-[42rem] overflow-auto"><table class="w-full text-sm"><thead class="sticky top-0 bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Fecha</th><th class="px-3 py-3">Usuario</th><th class="px-3 py-3">Entidad</th><th class="px-3 py-3">Acción</th><th class="px-3 py-3">Descripción</th><th class="px-3 py-3">Cambios</th></tr></thead><tbody class="divide-y divide-slate-100">
      <tr v-for="event in events.data" :key="event.id"><td class="px-4 py-3 whitespace-nowrap">{{ date(event.created_at) }}</td><td class="px-3 py-3">{{ event.user?.name || 'Sistema' }}</td><td class="px-3 py-3"><strong>{{ entity(event.entity_type) }}</strong><p class="text-xs text-slate-500">#{{ event.entity_id }}</p></td><td class="px-3 py-3"><span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700">{{ event.action }}</span></td><td class="px-3 py-3 min-w-52">{{ event.description || '—' }}</td><td class="px-3 py-3"><details v-if="event.old_values || event.new_values"><summary class="cursor-pointer text-xs font-semibold text-blue-700">Ver detalle</summary><div class="mt-2 max-w-md overflow-auto rounded-lg bg-slate-950 p-2 text-[10px] text-slate-100"><pre>Antes: {{ JSON.stringify(event.old_values, null, 2) }}</pre><pre>Después: {{ JSON.stringify(event.new_values, null, 2) }}</pre></div></details><span v-else>—</span></td></tr>
      <tr v-if="!events.data.length"><td colspan="6" class="px-4 py-12 text-center text-slate-500"><History class="mx-auto mb-2 size-7" />No hay eventos con estos filtros.</td></tr>
    </tbody></table></div><div v-if="events.links?.length > 3" class="flex flex-wrap gap-1 border-t p-3"><Link v-for="link in events.links.filter((item) => item.url)" :key="link.label" :href="link.url" :class="['rounded-lg px-3 py-2 text-xs', link.active ? 'bg-blue-600 text-white' : 'bg-slate-100']" v-html="link.label" /></div></section>
  </div></AppShell>
</template>
