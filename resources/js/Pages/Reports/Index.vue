<script setup>
import AppShell from '../../Components/AppShell.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import {
  BarChart3,
  CheckCircle2,
  ExternalLink,
  FileDown,
  FlaskConical,
  Search,
} from '@lucide/vue'

const props = defineProps({
  reports: { type: Array, required: true },
  summary: { type: Object, required: true },
})

const search = ref('')
const today = new Date()
const monthAgo = new Date(today)
monthAgo.setDate(today.getDate() - 30)
const asDate = (date) => date.toISOString().slice(0, 10)
const from = ref(asDate(monthAgo))
const to = ref(asDate(today))
const reportUrl = (url) => `${url}${url.includes('?') ? '&' : '?'}from=${encodeURIComponent(from.value)}&to=${encodeURIComponent(to.value)}`
const visibleReports = computed(() => {
  const term = search.value.trim().toLocaleLowerCase('es')

  if (!term) return props.reports

  return props.reports.filter((report) => `${report.title} ${report.description}`.toLocaleLowerCase('es').includes(term))
})
</script>

<template>
  <AppShell>
    <Head title="Centro de reportes" />

    <div class="mx-auto max-w-7xl space-y-6">
      <header class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 p-6 text-white shadow-xl sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-3xl">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold ring-1 ring-white/15">
              <BarChart3 class="size-4" />
              Northlink Quebar · Información administrativa
            </div>
            <h1 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">Centro de reportes</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
              Consulta cada área autorizada, revisa su estado y abre un resumen PDF para reuniones y seguimiento gerencial.
            </p>
          </div>

          <div class="grid grid-cols-3 gap-2 sm:gap-3">
            <div class="rounded-2xl bg-white/10 p-3 text-center ring-1 ring-white/15"><strong class="block text-2xl">{{ summary.available }}</strong><span class="text-[11px] text-slate-300">Disponibles</span></div>
            <div class="rounded-2xl bg-white/10 p-3 text-center ring-1 ring-white/15"><strong class="block text-2xl text-emerald-300">{{ summary.functional }}</strong><span class="text-[11px] text-slate-300">Funcionales</span></div>
            <div class="rounded-2xl bg-white/10 p-3 text-center ring-1 ring-white/15"><strong class="block text-2xl text-amber-300">{{ summary.prototype }}</strong><span class="text-[11px] text-slate-300">En diseño</span></div>
          </div>
        </div>
      </header>

      <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_auto_auto] lg:items-end">
        <label class="relative block">
          <span class="sr-only">Buscar reporte</span>
          <Search class="absolute left-3 top-3 size-4 text-slate-400" />
          <input v-model="search" type="search" placeholder="Buscar por módulo o contenido…" class="min-h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
        </label>
        <label class="text-xs font-semibold text-slate-600">Desde
          <input v-model="from" type="date" :max="to" class="mt-1 block min-h-10 rounded-xl border border-slate-200 px-3 text-sm font-normal text-slate-800">
        </label>
        <label class="text-xs font-semibold text-slate-600">Hasta
          <input v-model="to" type="date" :min="from" class="mt-1 block min-h-10 rounded-xl border border-slate-200 px-3 text-sm font-normal text-slate-800">
        </label>
      </section>

      <section v-if="visibleReports.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article v-for="report in visibleReports" :key="report.module" class="flex min-h-60 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
          <div class="flex items-start justify-between gap-4">
            <div :class="['flex size-11 items-center justify-center rounded-xl', report.status === 'functional' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700']">
              <CheckCircle2 v-if="report.status === 'functional'" class="size-5" />
              <FlaskConical v-else class="size-5" />
            </div>
            <span :class="['rounded-full px-2.5 py-1 text-[11px] font-bold', report.status === 'functional' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700']">
              {{ report.status === 'functional' ? 'Funcional' : 'En diseño' }}
            </span>
          </div>

          <h2 class="mt-4 font-display text-lg font-bold text-slate-950">{{ report.title }}</h2>
          <p class="mt-2 flex-1 text-sm leading-6 text-slate-600">{{ report.description }}</p>

          <div class="mt-5 grid grid-cols-3 gap-2">
            <Link :href="report.href" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-slate-950 px-3 text-sm font-semibold text-white hover:bg-blue-700">
              <ExternalLink class="size-4" /> Abrir módulo
            </Link>
            <a :href="reportUrl(report.pdf_url)" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:border-blue-300 hover:text-blue-700">
              <FileDown class="size-4" /> PDF
            </a>
            <a :href="reportUrl(report.excel_url)" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-emerald-200 px-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-50"><FileDown class="size-4" /> Excel</a>
          </div>
        </article>
      </section>

      <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
        <Search class="mx-auto size-8 text-slate-300" />
        <h2 class="mt-3 font-semibold text-slate-900">No encontramos reportes</h2>
        <p class="mt-1 text-sm text-slate-500">Prueba con otro nombre o limpia la búsqueda.</p>
      </div>
    </div>
  </AppShell>
</template>
