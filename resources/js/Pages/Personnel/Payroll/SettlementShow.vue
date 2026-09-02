<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { AlertTriangle, ArrowLeft, Banknote, CalendarRange, CheckCircle2, Pencil, Printer, ShieldCheck, Trash2, UserMinus, X } from '@lucide/vue'
import AppShell from '../../../Components/AppShell.vue'

const props = defineProps({ settlement: Object })

const showMarkPaid = ref(false)
const markPaidForm = useForm({ payment_method: 'efectivo' })

const showEdit = ref(false)
const editForm = useForm({
  employee_id: props.settlement.employee_id,
  termination_type: props.settlement.termination_type,
  termination_date: props.settlement.termination_date?.slice(0, 10) ?? '',
  pending_salary_start: props.settlement.pending_salary_start?.slice(0, 10) ?? '',
  severance_method: props.settlement.severance_method,
  severance_amount: props.settlement.severance_amount,
  notes: props.settlement.notes ?? '',
})

const terminationTypeLabels = {
  unjustified_dismissal: 'Despido sin causa',
  resignation: 'Renuncia voluntaria',
  justified_dismissal: 'Despido con causa justificada',
  mutual_agreement: 'Mutuo acuerdo',
}
const terminationTypeLabel = (value) => terminationTypeLabels[value] ?? value

const statusMeta = (value) => ({
  draft: { label: 'Borrador', class: 'bg-[#F2F2F7] text-[#6E6E73]' },
  approved: { label: 'Aprobada', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  paid: { label: 'Pagada', class: 'bg-[#E9F8EE] text-[#187A31]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })

const paymentMethodLabel = (value) => ({
  efectivo: 'Efectivo',
  transferencia: 'Transferencia bancaria',
}[value] ?? value)

const money = (value) => new Intl.NumberFormat('es-NI', { style: 'currency', currency: 'NIO' }).format(value ?? 0)
const dateLabel = (value) => value
  ? new Intl.DateTimeFormat('es-NI', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
  : '—'

const approve = () => {
  router.patch(`/settlements/${props.settlement.id}/approve`, {}, { preserveScroll: true })
}
const openMarkPaid = () => {
  markPaidForm.reset()
  markPaidForm.clearErrors()
  showMarkPaid.value = true
}
const submitMarkPaid = () => {
  markPaidForm.patch(`/settlements/${props.settlement.id}/pay`, {
    preserveScroll: true,
    onSuccess: () => { showMarkPaid.value = false },
  })
}
const destroy = () => {
  if (confirm('¿Eliminar esta liquidación en borrador?')) {
    router.delete(`/settlements/${props.settlement.id}`)
  }
}
const print = () => window.open(`/settlements/${props.settlement.id}/export`, '_blank')

const openEdit = () => {
  editForm.clearErrors()
  editForm.employee_id = props.settlement.employee_id
  editForm.termination_type = props.settlement.termination_type
  editForm.termination_date = props.settlement.termination_date?.slice(0, 10) ?? ''
  editForm.pending_salary_start = props.settlement.pending_salary_start?.slice(0, 10) ?? ''
  editForm.severance_method = props.settlement.severance_method
  editForm.severance_amount = props.settlement.severance_amount
  editForm.notes = props.settlement.notes ?? ''
  showEdit.value = true
}
const closeEdit = () => {
  if (!editForm.processing) {
    showEdit.value = false
  }
}
const submitEdit = () => {
  editForm.put(`/settlements/${props.settlement.id}`, {
    preserveScroll: true,
    onSuccess: () => { showEdit.value = false },
  })
}
</script>

<template>
  <AppShell>
    <Head :title="`Liquidación ${settlement.code}`" />
    <div class="mx-auto max-w-4xl">
      <Link href="/payroll" class="mb-4 inline-flex items-center gap-1.5 text-xs font-semibold text-[#007AFF] print:hidden"><ArrowLeft class="size-3.5" /> Volver a Nómina</Link>

      <header class="mb-5 flex flex-col gap-4 rounded-2xl border border-[#E5E5E5] bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <div class="flex size-12 items-center justify-center rounded-xl bg-[#D70015]/10 text-[#D70015]"><UserMinus class="size-6" /></div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="font-display text-xl font-bold text-[#1D1D1F]">{{ settlement.employee?.full_name }}</h1>
              <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', statusMeta(settlement.status).class]">{{ statusMeta(settlement.status).label }}</span>
            </div>
            <p class="mt-1 flex items-center gap-1.5 text-xs text-[#6E6E73]">
              {{ settlement.code }} · {{ settlement.employee?.role?.name }} · {{ terminationTypeLabel(settlement.termination_type) }}
            </p>
            <p class="mt-1 flex items-center gap-1.5 text-xs text-[#6E6E73]">
              <CalendarRange class="size-3.5" />Ingreso {{ dateLabel(settlement.hired_at) }} — Salida {{ dateLabel(settlement.termination_date) }} ({{ settlement.tenure_days }} días de antigüedad)
            </p>
          </div>
        </div>
        <div class="flex gap-2 print:hidden">
          <button type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#E5E5E5] px-4 text-sm font-semibold text-[#6E6E73] hover:bg-[#F5F5F7]" @click="print"><Printer class="size-4" /> Imprimir</button>
          <button v-if="settlement.status === 'draft'" type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#E5E5E5] px-4 text-sm font-semibold text-[#D70015] hover:bg-[#FFE5E5]" @click="destroy"><Trash2 class="size-4" /> Eliminar</button>
          <button v-if="settlement.status !== 'paid'" type="button" class="inline-flex h-10 items-center gap-2 rounded-xl border border-[#E5E5E5] px-4 text-sm font-semibold text-[#007AFF] hover:bg-[#E8F2FF]" @click="openEdit"><Pencil class="size-4" /> Editar</button>
          <button v-if="settlement.status === 'draft'" type="button" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#0066D6]" @click="approve"><CheckCircle2 class="size-4" /> Aprobar</button>
          <button v-if="settlement.status === 'approved'" type="button" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#34C759] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#2AAE4C]" @click="openMarkPaid"><Banknote class="size-4" /> Marcar como pagada</button>
        </div>
      </header>

      <div v-if="settlement.status !== 'paid'" class="mb-4 flex items-start gap-2 rounded-xl bg-[#FFF6E5] p-3 text-xs text-[#8A6300] print:hidden">
        <AlertTriangle class="mt-0.5 size-3.5 shrink-0" />
        <span>Al marcar esta liquidación como pagada, se saldarán los préstamos y deducciones pendientes del colaborador y se desactivará automáticamente en el sistema.</span>
      </div>

      <section class="mb-4 overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm">
        <div class="divide-y divide-[#EFEFF1]">
          <div class="flex items-center justify-between px-5 py-3">
            <div>
              <p class="text-sm font-semibold text-[#1D1D1F]">Salario pendiente</p>
              <p class="text-xs text-[#8E8E93]">{{ dateLabel(settlement.pending_salary_start) }} – {{ dateLabel(settlement.pending_salary_end) }} · {{ settlement.pending_salary_days }} día(s)</p>
            </div>
            <p class="text-sm font-semibold">{{ money(settlement.pending_salary_amount) }}</p>
          </div>
          <div class="flex items-center justify-between px-5 py-3">
            <div>
              <p class="text-sm font-semibold text-[#1D1D1F]">Vacaciones proporcionales no gozadas</p>
              <p class="text-xs text-[#8E8E93]">{{ settlement.vacation_days_pending }} día(s) acumulados</p>
            </div>
            <p class="text-sm font-semibold">{{ money(settlement.vacation_amount) }}</p>
          </div>
          <div class="flex items-center justify-between px-5 py-3">
            <div>
              <p class="text-sm font-semibold text-[#1D1D1F]">Aguinaldo proporcional</p>
              <p class="text-xs text-[#8E8E93]">{{ settlement.aguinaldo_days }} / 360 días del periodo vigente</p>
            </div>
            <p class="text-sm font-semibold">{{ money(settlement.aguinaldo_amount) }}</p>
          </div>
          <div class="flex items-center justify-between px-5 py-3">
            <div>
              <p class="text-sm font-semibold text-[#1D1D1F]">Indemnización por antigüedad</p>
              <p class="text-xs text-[#8E8E93]">{{ settlement.severance_method === 'legal' ? 'Calculada según Art. 45 del Código del Trabajo' : 'Monto manual' }}</p>
            </div>
            <p class="text-sm font-semibold">{{ money(settlement.severance_amount) }}</p>
          </div>
          <div class="flex items-center justify-between bg-[#FAFAFB] px-5 py-3">
            <p class="text-sm font-semibold text-[#1D1D1F]">Subtotal bruto</p>
            <p class="text-sm font-bold text-[#1D1D1F]">{{ money(settlement.gross_amount) }}</p>
          </div>
          <div v-if="Number(settlement.loan_deduction) > 0" class="flex items-center justify-between px-5 py-3">
            <p class="text-sm text-[#D70015]">Saldo de préstamos activos</p>
            <p class="text-sm font-semibold text-[#D70015]">-{{ money(settlement.loan_deduction) }}</p>
          </div>
          <div v-if="Number(settlement.other_deduction) > 0" class="flex items-center justify-between px-5 py-3">
            <p class="text-sm text-[#D70015]">Deducciones pendientes</p>
            <p class="text-sm font-semibold text-[#D70015]">-{{ money(settlement.other_deduction) }}</p>
          </div>
          <div class="flex items-center justify-between bg-[#E9F8EE] px-5 py-4">
            <p class="text-base font-bold text-[#1D1D1F]">Neto a pagar</p>
            <p class="text-xl font-bold text-[#187A31]">{{ money(settlement.net_amount) }}</p>
          </div>
        </div>
      </section>

      <section v-if="settlement.status === 'paid'" class="mb-4 rounded-2xl border border-[#E5E5E5] bg-white p-4 text-sm text-[#6E6E73] shadow-sm">
        Pagada el {{ dateLabel(settlement.paid_at) }} · {{ paymentMethodLabel(settlement.payment_method) }}
      </section>

      <section v-if="settlement.notes" class="rounded-2xl border border-[#E5E5E5] bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#8E8E93]">Notas</p>
        <p class="mt-1 text-sm text-[#1D1D1F]">{{ settlement.notes }}</p>
      </section>
    </div>

    <div v-if="showMarkPaid" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showMarkPaid = false">
      <form class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitMarkPaid">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#E9F8EE] text-[#34C759]"><Banknote class="size-5" /></div><div><h2 class="text-lg font-bold">Marcar como pagada</h2><p class="text-xs text-[#8E8E93]">{{ money(settlement.net_amount) }} para {{ settlement.employee?.full_name }}.</p></div></div>
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
          <div class="flex items-start gap-2 rounded-xl bg-[#FFF6E5] p-3 text-xs text-[#8A6300]">
            <AlertTriangle class="mt-0.5 size-3.5 shrink-0" />
            <span>Esta acción saldará préstamos/deducciones pendientes y desactivará al colaborador.</span>
          </div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4">
          <button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showMarkPaid = false">Cancelar</button>
          <button type="submit" :disabled="markPaidForm.processing" class="h-10 rounded-xl bg-[#34C759] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ markPaidForm.processing ? 'Guardando...' : 'Confirmar pago' }}</button>
        </footer>
      </form>
    </div>

    <div v-if="showEdit" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="closeEdit">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitEdit">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#E8F2FF] text-[#007AFF]"><Pencil class="size-5" /></div><div><h2 class="text-lg font-bold">Corregir liquidación</h2><p class="text-xs text-[#8E8E93]">{{ settlement.employee?.full_name }}</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="closeEdit"><X class="size-4" /></button>
        </header>
        <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5">
          <div>
            <label class="mb-1 block text-xs font-semibold">Tipo de terminación</label>
            <select v-model="editForm.termination_type" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              <option value="unjustified_dismissal">Despido sin causa</option>
              <option value="resignation">Renuncia voluntaria</option>
              <option value="justified_dismissal">Despido con causa justificada</option>
              <option value="mutual_agreement">Mutuo acuerdo</option>
            </select>
            <p v-if="editForm.errors.termination_type" class="mt-1 text-xs text-[#D70015]">{{ editForm.errors.termination_type }}</p>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold">Fecha de salida</label>
              <input v-model="editForm.termination_date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              <p v-if="editForm.errors.termination_date" class="mt-1 text-xs text-[#D70015]">{{ editForm.errors.termination_date }}</p>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold">Salario pendiente desde</label>
              <input v-model="editForm.pending_salary_start" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              <p v-if="editForm.errors.pending_salary_start" class="mt-1 text-xs text-[#D70015]">{{ editForm.errors.pending_salary_start }}</p>
            </div>
          </div>
          <div>
            <p class="mb-2 text-xs font-semibold">Indemnización por antigüedad</p>
            <div class="flex gap-2">
              <button type="button" :class="['flex-1 rounded-xl border px-3 py-2 text-xs font-semibold', editForm.severance_method === 'legal' ? 'border-[#D70015] bg-[#FFE5E5] text-[#D70015]' : 'border-[#E5E5E5] text-[#6E6E73]']" @click="editForm.severance_method = 'legal'">Ley de Nicaragua</button>
              <button type="button" :class="['flex-1 rounded-xl border px-3 py-2 text-xs font-semibold', editForm.severance_method === 'manual' ? 'border-[#D70015] bg-[#FFE5E5] text-[#D70015]' : 'border-[#E5E5E5] text-[#6E6E73]']" @click="editForm.severance_method = 'manual'">Monto manual</button>
            </div>
            <input v-if="editForm.severance_method === 'manual'" v-model="editForm.severance_amount" type="number" step="0.01" min="0" placeholder="Monto de indemnización" class="mt-2 h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
            <p v-if="editForm.errors.severance_amount" class="mt-1 text-xs text-[#D70015]">{{ editForm.errors.severance_amount }}</p>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="editForm.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
          <div class="flex items-start gap-2 rounded-xl bg-[#F1F6FF] p-3 text-xs text-[#245DA8]">
            <ShieldCheck class="mt-0.5 size-3.5 shrink-0" />
            <span>Vacaciones, aguinaldo y descuentos pendientes se recalculan automáticamente con los datos actuales.</span>
          </div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4">
          <button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="closeEdit">Cancelar</button>
          <button type="submit" :disabled="editForm.processing" class="h-10 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ editForm.processing ? 'Guardando...' : 'Guardar corrección' }}</button>
        </footer>
      </form>
    </div>
  </AppShell>
</template>
