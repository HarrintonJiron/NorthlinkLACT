<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Banknote, CalendarRange, CheckCircle2, Gift, Printer, ShieldCheck, Trash2 } from '@lucide/vue'
import AppShell from '../../../Components/AppShell.vue'

const props = defineProps({ period: Object, totals: Object })

const statusMeta = (value) => ({
  draft: { label: 'Borrador', class: 'bg-[#F2F2F7] text-[#6E6E73]' },
  approved: { label: 'Aprobado', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  paid: { label: 'Pagado', class: 'bg-[#E9F8EE] text-[#187A31]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })

const money = (value) => new Intl.NumberFormat('es-NI', { style: 'currency', currency: 'NIO' }).format(value ?? 0)
const dateLabel = (value) => value
  ? new Intl.DateTimeFormat('es-NI', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
  : '—'

const approve = () => {
  router.patch(`/aguinaldo/${props.period.id}/approve`, {}, { preserveScroll: true })
}
const markPaid = () => {
  router.patch(`/aguinaldo/${props.period.id}/pay`, {}, { preserveScroll: true })
}
const destroy = () => {
  if (confirm('¿Eliminar este aguinaldo en borrador?')) {
    router.delete(`/aguinaldo/${props.period.id}`)
  }
}
const print = () => window.open(`/aguinaldo/${props.period.id}/export`, '_blank')
</script>

<template>
  <AppShell>
    <Head :title="`Aguinaldo ${period.code}`" />
    <div class="mx-auto max-w-7xl">
      <Link href="/payroll" class="mb-4 inline-flex items-center gap-1.5 text-xs font-semibold text-[#007AFF] print:hidden"><ArrowLeft class="size-3.5" /> Volver a Nómina</Link>

      <header class="mb-5 flex flex-col gap-4 rounded-2xl border border-[#E5E5E5] bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <div class="flex size-12 items-center justify-center rounded-xl bg-[#007AFF]/10 text-[#007AFF]"><Gift class="size-6" /></div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="font-display text-xl font-bold text-[#1D1D1F]">{{ period.code }} · Aguinaldo {{ period.year }}</h1>
              <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', statusMeta(period.status).class]">{{ statusMeta(period.status).label }}</span>
            </div>
            <p class="mt-1 flex items-center gap-1.5 text-xs text-[#6E6E73]">
              <CalendarRange class="size-3.5" />{{ dateLabel(period.period_start) }} – {{ dateLabel(period.period_end) }} · Exento de INSS e IR
            </p>
          </div>
        </div>
        <div class="flex gap-2 print:hidden">
          <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#E5E5E5] px-4 text-sm font-semibold text-[#6E6E73] hover:bg-[#F5F5F7]" @click="print"><Printer class="size-4" /> Imprimir</button>
          <button v-if="period.status === 'draft'" type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#E5E5E5] px-4 text-sm font-semibold text-[#D70015] hover:bg-[#FFE5E5]" @click="destroy"><Trash2 class="size-4" /> Eliminar</button>
          <button v-if="period.status === 'draft'" type="button" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#0066D6]" @click="approve"><CheckCircle2 class="size-4" /> Aprobar</button>
          <button v-if="period.status === 'approved'" type="button" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#34C759] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#2AAE4C]" @click="markPaid"><Banknote class="size-4" /> Marcar como pagado</button>
        </div>
      </header>

      <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Total a pagar</p>
          <p class="mt-1 text-xl font-bold text-[#187A31]">{{ money(totals.amount) }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Colaboradores incluidos</p>
          <p class="mt-1 text-xl font-bold text-[#007AFF]">{{ period.items.length }}</p>
        </div>
      </div>

      <section class="overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-[#E5E5E5] bg-[#FAFAFB] text-left text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">
                <th class="px-4 py-3">Colaborador</th>
                <th class="px-4 py-3">Rol</th>
                <th class="px-4 py-3 text-right">Sueldo base</th>
                <th class="px-4 py-3 text-right">Días laborados</th>
                <th class="px-4 py-3 text-right">Meses</th>
                <th class="px-4 py-3 text-right">Aguinaldo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EFEFF1]">
              <tr v-for="item in period.items" :key="item.id" class="hover:bg-[#F8FAFF]">
                <td class="px-4 py-3">
                  <p class="font-medium text-[#1D1D1F]">{{ item.employee?.full_name }}</p>
                  <p class="text-xs text-[#8E8E93]">{{ item.employee?.identity_number || 'Sin identificación' }}</p>
                </td>
                <td class="px-4 py-3 text-[#6E6E73]">{{ item.employee?.role?.name }}</td>
                <td class="px-4 py-3 text-right">{{ money(item.base_salary) }}</td>
                <td class="px-4 py-3 text-right text-[#6E6E73]">{{ item.days_employed }} / 360</td>
                <td class="px-4 py-3 text-right text-[#6E6E73]">{{ (item.days_employed / 30).toFixed(1) }}</td>
                <td class="px-4 py-3 text-right font-semibold text-[#187A31]">{{ money(item.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="!period.items.length" class="flex flex-col items-center py-14 text-center">
          <ShieldCheck class="size-10 text-[#C7C7CC]" /><p class="mt-3 text-sm font-semibold">No hay colaboradores en este aguinaldo</p>
          <p class="mt-1 text-xs text-[#8E8E93]">Verifica que existan colaboradores activos con sueldo asignado y fecha de ingreso dentro del periodo.</p>
        </div>
      </section>
    </div>
  </AppShell>
</template>
