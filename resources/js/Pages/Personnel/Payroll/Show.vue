<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Banknote, CalendarRange, CheckCircle2, Landmark, Pencil, Printer, Search, ShieldCheck, Trash2, Wallet, X } from '@lucide/vue'
import AppShell from '../../../Components/AppShell.vue'

const props = defineProps({ period: Object, totals: Object })

const search = ref('')
const roleFilter = ref('all')
const showMarkPaid = ref(false)
const markPaidForm = useForm({ payment_method: 'efectivo' })

const editingItem = ref(null)
const itemForm = useForm({ gross_salary: '', other_deductions: '' })

const paymentMethodLabel = (value) => ({
  efectivo: 'Efectivo',
  transferencia: 'Transferencia bancaria',
}[value] ?? value)

const percent = (value) => `${(Number(value) * 100).toFixed(2).replace(/\.?0+$/, '')}%`

const roles = computed(() => {
  const unique = new Map()
  props.period.items.forEach((item) => {
    if (item.employee?.role) unique.set(item.employee.role.id, item.employee.role.name)
  })
  return Array.from(unique, ([id, name]) => ({ id, name }))
})

const filteredItems = computed(() => {
  const term = search.value.trim().toLocaleLowerCase()
  return props.period.items.filter((item) => {
    if (roleFilter.value !== 'all' && String(item.employee?.role?.id) !== roleFilter.value) return false
    if (!term) return true
    return (item.employee?.full_name ?? '').toLocaleLowerCase().includes(term)
  })
})

const frequencyLabel = (value) => ({
  weekly: 'Semanal',
  biweekly: 'Quincenal',
  monthly: 'Mensual',
}[value] ?? value)

const statusMeta = (value) => ({
  draft: { label: 'Borrador', class: 'bg-[#F2F2F7] text-[#6E6E73]' },
  approved: { label: 'Aprobada', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  paid: { label: 'Pagada', class: 'bg-[#E9F8EE] text-[#187A31]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })

const money = (value) => new Intl.NumberFormat('es-NI', { style: 'currency', currency: 'NIO' }).format(value ?? 0)
const dateLabel = (value) => value
  ? new Intl.DateTimeFormat('es-NI', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
  : '—'

const approve = () => {
  router.patch(`/payroll/${props.period.id}/approve`, {}, { preserveScroll: true })
}
const openMarkPaid = () => {
  markPaidForm.reset()
  markPaidForm.clearErrors()
  showMarkPaid.value = true
}
const submitMarkPaid = () => {
  markPaidForm.patch(`/payroll/${props.period.id}/pay`, {
    preserveScroll: true,
    onSuccess: () => { showMarkPaid.value = false },
  })
}
const destroy = () => {
  if (confirm('¿Eliminar esta planilla en borrador?')) {
    router.delete(`/payroll/${props.period.id}`)
  }
}
const print = () => {
  // Generates a real PDF server-side with every colaborador in the planilla,
  // independent of whatever search/role filter is active on screen — avoids the
  // unreliability of window.print() + CSS across browsers.
  window.open(`/payroll/${props.period.id}/export`, '_blank')
}

const openItemEdit = (item) => {
  itemForm.reset()
  itemForm.clearErrors()
  editingItem.value = item
  itemForm.gross_salary = item.gross_salary
  itemForm.other_deductions = item.other_deductions
}
const closeItemEdit = () => {
  if (!itemForm.processing) {
    editingItem.value = null
  }
}
const submitItemEdit = () => {
  itemForm.put(`/payroll/${props.period.id}/items/${editingItem.value.id}`, {
    preserveScroll: true,
    onSuccess: () => { editingItem.value = null },
  })
}
</script>

<template>
  <AppShell>
    <Head :title="`Planilla ${period.code}`" />
    <div class="mx-auto max-w-7xl">
      <Link href="/payroll" class="mb-4 inline-flex items-center gap-1.5 text-xs font-semibold text-[#007AFF] print:hidden"><ArrowLeft class="size-3.5" /> Volver a Nómina</Link>

      <header class="mb-5 flex flex-col gap-4 rounded-2xl border border-[#E5E5E5] bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <div class="flex size-12 items-center justify-center rounded-xl bg-[#007AFF]/10 text-[#007AFF]"><Wallet class="size-6" /></div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="font-display text-xl font-bold text-[#1D1D1F]">{{ period.code }}</h1>
              <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', statusMeta(period.status).class]">{{ statusMeta(period.status).label }}</span>
            </div>
            <p class="mt-1 flex items-center gap-1.5 text-xs text-[#6E6E73]">
              <CalendarRange class="size-3.5" />{{ dateLabel(period.period_start) }} – {{ dateLabel(period.period_end) }} · {{ frequencyLabel(period.pay_frequency) }}
              <span v-if="period.payment_method"> · {{ paymentMethodLabel(period.payment_method) }}</span>
              <span v-if="period.tax_policy"> · {{ period.tax_policy.name }}</span>
            </p>
          </div>
        </div>
        <div class="flex gap-2 print:hidden">
          <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#E5E5E5] px-4 text-sm font-semibold text-[#6E6E73] hover:bg-[#F5F5F7]" @click="print"><Printer class="size-4" /> Imprimir</button>
          <button v-if="period.status === 'draft'" type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#E5E5E5] px-4 text-sm font-semibold text-[#D70015] hover:bg-[#FFE5E5]" @click="destroy"><Trash2 class="size-4" /> Eliminar</button>
          <button v-if="period.status === 'draft'" type="button" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#0066D6]" @click="approve"><CheckCircle2 class="size-4" /> Aprobar</button>
          <button v-if="period.status === 'approved'" type="button" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#34C759] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#2AAE4C]" @click="openMarkPaid"><Banknote class="size-4" /> Marcar como pagada</button>
        </div>
      </header>

      <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Sueldos brutos</p>
          <p class="mt-1 text-xl font-bold text-[#1D1D1F]">{{ money(totals.gross) }}</p>
          <p v-if="totals.bonus > 0" class="text-xs text-[#34C759]">incluye {{ money(totals.bonus) }} en bonos</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">INSS laboral{{ period.tax_policy ? ` (${percent(period.tax_policy.inss_employee_rate)})` : '' }}</p>
          <p class="mt-1 text-xl font-bold text-[#D70015]">{{ money(totals.inss_employee) }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">IR retenido</p>
          <p class="mt-1 text-xl font-bold text-[#D70015]">{{ money(totals.ir) }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Neto a pagar</p>
          <p class="mt-1 text-xl font-bold text-[#187A31]">{{ money(totals.net_pay) }}</p>
          <p v-if="totals.deduction > 0" class="text-xs text-[#D70015]">-{{ money(totals.deduction) }} en deducciones</p>
          <p v-if="totals.loan_deduction > 0" class="text-xs text-[#D70015]">-{{ money(totals.loan_deduction) }} en préstamos</p>
        </div>
      </div>

      <section class="mb-4 rounded-2xl border border-[#E5E5E5] bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2 text-xs font-semibold text-[#6E6E73]"><Landmark class="size-3.5" /> Cargas patronales (informativo, no afectan el neto del colaborador)</div>
        <div class="mt-2 flex flex-wrap gap-6 text-sm">
          <p><span class="text-[#8E8E93]">INSS patronal{{ period.tax_policy ? ` (${percent(period.tax_policy.inss_employer_rate)})` : '' }}:</span> <span class="font-semibold">{{ money(totals.inss_employer) }}</span></p>
          <p><span class="text-[#8E8E93]">INATEC{{ period.tax_policy ? ` (${percent(period.tax_policy.inatec_rate)})` : '' }}:</span> <span class="font-semibold">{{ money(totals.inatec_employer) }}</span></p>
        </div>
      </section>

      <section class="overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm print:border-0 print:shadow-none">
        <div class="flex flex-col gap-3 border-b border-[#E5E5E5] p-4 sm:flex-row sm:items-center print:hidden">
          <div class="relative flex-1">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-[#8E8E93]" />
            <input v-model="search" type="search" placeholder="Buscar colaborador..." class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] pl-9 pr-3 text-sm focus:ring-2 focus:ring-[#007AFF]/20">
          </div>
          <select v-model="roleFilter" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-xs font-semibold text-[#6E6E73] focus:ring-2 focus:ring-[#007AFF]/20">
            <option value="all">Todos los roles</option>
            <option v-for="role in roles" :key="role.id" :value="String(role.id)">{{ role.name }}</option>
          </select>
        </div>
        <div class="overflow-x-auto print:overflow-visible">
          <table class="w-full text-sm print:text-[9px]">
            <thead>
              <tr class="border-b border-[#E5E5E5] bg-[#FAFAFB] text-left text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93] print:text-[7.5px]">
                <th class="px-4 py-3 print:px-1.5 print:py-1.5">Colaborador</th>
                <th class="px-4 py-3 print:px-1.5 print:py-1.5">Rol</th>
                <th class="px-4 py-3 text-right print:px-1.5 print:py-1.5">Base + bono</th>
                <th class="px-4 py-3 text-right print:px-1.5 print:py-1.5">INSS (7%)</th>
                <th class="px-4 py-3 text-right print:px-1.5 print:py-1.5">IR</th>
                <th class="px-4 py-3 text-right print:px-1.5 print:py-1.5">Préstamo</th>
                <th class="px-4 py-3 text-right print:px-1.5 print:py-1.5">Días trab.</th>
                <th class="px-4 py-3 text-right print:px-1.5 print:py-1.5">Neto a pagar</th>
                <th v-if="period.status !== 'paid'" class="px-4 py-3 print:hidden"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EFEFF1]">
              <tr v-for="item in filteredItems" :key="item.id" class="hover:bg-[#F8FAFF]">
                <td class="px-4 py-3 print:px-1.5 print:py-1">
                  <p class="font-medium text-[#1D1D1F]">{{ item.employee?.full_name }}</p>
                  <p class="text-xs text-[#8E8E93] print:text-[7.5px]">{{ item.employee?.identity_number || 'Sin identificación' }}</p>
                </td>
                <td class="px-4 py-3 text-[#6E6E73] print:px-1.5 print:py-1">{{ item.employee?.role?.name }}</td>
                <td class="px-4 py-3 text-right print:px-1.5 print:py-1">
                  <p>{{ money(item.gross_salary) }}</p>
                  <p v-if="Number(item.bonus_amount) > 0" class="text-xs text-[#34C759] print:text-[7.5px]">+{{ money(item.bonus_amount) }} bono</p>
                  <p v-if="Number(item.deduction_amount) > 0" class="text-xs text-[#D70015] print:text-[7.5px]">-{{ money(item.deduction_amount) }} deducción</p>
                  <p v-if="Number(item.other_deductions) > 0" class="text-xs text-[#D70015] print:text-[7.5px]">-{{ money(item.other_deductions) }} ajuste</p>
                </td>
                <td class="px-4 py-3 text-right text-[#D70015] print:px-1.5 print:py-1">-{{ money(item.inss_employee) }}</td>
                <td class="px-4 py-3 text-right text-[#D70015] print:px-1.5 print:py-1">-{{ money(item.ir_amount) }}</td>
                <td class="px-4 py-3 text-right text-[#D70015] print:px-1.5 print:py-1">{{ Number(item.loan_deduction) > 0 ? `-${money(item.loan_deduction)}` : '—' }}</td>
                <td class="px-4 py-3 text-right text-[#6E6E73] print:px-1.5 print:py-1">{{ item.days_worked ?? '—' }}</td>
                <td class="px-4 py-3 text-right font-semibold text-[#187A31] print:px-1.5 print:py-1">{{ money(item.net_pay) }}</td>
                <td v-if="period.status !== 'paid'" class="px-4 py-3 text-right print:hidden">
                  <button type="button" class="rounded-lg p-1.5 text-[#007AFF] hover:bg-[#E8F2FF]" title="Corregir esta línea" @click="openItemEdit(item)"><Pencil class="size-4" /></button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="!period.items.length" class="flex flex-col items-center py-14 text-center">
          <ShieldCheck class="size-10 text-[#C7C7CC]" /><p class="mt-3 text-sm font-semibold">No hay colaboradores en esta planilla</p>
          <p class="mt-1 text-xs text-[#8E8E93]">Verifica que existan colaboradores activos con sueldo asignado.</p>
        </div>
        <p v-else-if="!filteredItems.length" class="p-8 text-center text-sm text-[#8E8E93]">Ningún colaborador coincide con estos filtros.</p>
      </section>
    </div>

    <div v-if="showMarkPaid" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showMarkPaid = false">
      <form class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitMarkPaid">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#E9F8EE] text-[#34C759]"><Banknote class="size-5" /></div><div><h2 class="text-lg font-bold">Marcar como pagada</h2><p class="text-xs text-[#8E8E93]">{{ money(totals.net_pay) }} para {{ period.items.length }} colaborador(es).</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showMarkPaid = false"><X class="size-4" /></button>
        </header>
        <div class="space-y-2 p-5">
          <label class="mb-1 block text-xs font-semibold">Forma de pago</label>
          <label class="flex items-center gap-3 rounded-xl border border-[#E5E5E5] p-3 has-[:checked]:border-[#34C759] has-[:checked]:bg-[#E9F8EE]">
            <input v-model="markPaidForm.payment_method" type="radio" value="efectivo" class="text-[#34C759]">
            <span class="text-sm font-medium">Efectivo</span>
          </label>
          <label class="flex items-center gap-3 rounded-xl border border-[#E5E5E5] p-3 has-[:checked]:border-[#34C759] has-[:checked]:bg-[#E9F8EE]">
            <input v-model="markPaidForm.payment_method" type="radio" value="transferencia" class="text-[#34C759]">
            <span class="text-sm font-medium">Transferencia bancaria</span>
          </label>
          <p v-if="markPaidForm.errors.payment_method" class="mt-1 text-xs text-[#D70015]">{{ markPaidForm.errors.payment_method }}</p>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4">
          <button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showMarkPaid = false">Cancelar</button>
          <button type="submit" :disabled="markPaidForm.processing" class="h-10 rounded-xl bg-[#34C759] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ markPaidForm.processing ? 'Guardando...' : 'Confirmar pago' }}</button>
        </footer>
      </form>
    </div>

    <div v-if="editingItem" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="closeItemEdit">
      <form class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitItemEdit">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#E8F2FF] text-[#007AFF]"><Pencil class="size-5" /></div><div><h2 class="text-lg font-bold">Corregir línea</h2><p class="text-xs text-[#8E8E93]">{{ editingItem.employee?.full_name }}</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="closeItemEdit"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div>
            <label class="mb-1 block text-xs font-semibold">Sueldo bruto (base + bono)</label>
            <input v-model="itemForm.gross_salary" type="number" step="0.01" min="0" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
            <p v-if="itemForm.errors.gross_salary" class="mt-1 text-xs text-[#D70015]">{{ itemForm.errors.gross_salary }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold">Descuento manual <span class="font-normal text-[#8E8E93]">(opcional)</span></label>
            <input v-model="itemForm.other_deductions" type="number" step="0.01" min="0" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
            <p v-if="itemForm.errors.other_deductions" class="mt-1 text-xs text-[#D70015]">{{ itemForm.errors.other_deductions }}</p>
          </div>
          <div class="flex items-start gap-2 rounded-xl bg-[#F1F6FF] p-3 text-xs text-[#245DA8]">
            <ShieldCheck class="mt-0.5 size-3.5 shrink-0" />
            <span>INSS, IR y el neto se recalculan automáticamente a partir de este bruto.</span>
          </div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4">
          <button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="closeItemEdit">Cancelar</button>
          <button type="submit" :disabled="itemForm.processing" class="h-10 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ itemForm.processing ? 'Guardando...' : 'Guardar corrección' }}</button>
        </footer>
      </form>
    </div>
  </AppShell>
</template>
