<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import {
  ArrowLeft, Banknote, Briefcase, Calendar, CalendarDays, Check, CheckCircle2, Clock, HandCoins,
  Mail, Phone, Plus, ShieldCheck, Trash2, UserRound, Wallet, X, XCircle,
} from '@lucide/vue'
import AppShell from '../../../Components/AppShell.vue'

const props = defineProps({
  employee: Object,
  tenureDays: Number,
  daysWorkedThisMonth: Number,
  vacationBalance: Number,
  activeLoanBalance: Number,
  pendingBonusTotal: Number,
})

const success = ref('')
const showVacation = ref(false)
const showBonus = ref(false)
const showLoan = ref(false)
const showAbsence = ref(false)

const vacationForm = useForm({ start_date: '', end_date: '', notes: '' })
const bonusForm = useForm({ concept: '', amount: '', bonus_date: new Date().toISOString().slice(0, 10), notes: '' })
const loanForm = useForm({ amount: '', installment_amount: '', granted_at: new Date().toISOString().slice(0, 10), reason: '' })
const absenceForm = useForm({ date: new Date().toISOString().slice(0, 10), type: 'unjustified', notes: '' })

const money = (value) => new Intl.NumberFormat('es-NI', { style: 'currency', currency: 'NIO' }).format(value ?? 0)
const dateLabel = (value) => value
  ? new Intl.DateTimeFormat('es-NI', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
  : '—'
const tenureLabel = (days) => {
  const years = Math.floor(days / 365)
  const months = Math.floor((days % 365) / 30)
  if (years > 0) return `${years} año${years > 1 ? 's' : ''}${months > 0 ? `, ${months} mes${months > 1 ? 'es' : ''}` : ''}`
  if (months > 0) return `${months} mes${months > 1 ? 'es' : ''}`
  return `${days} día${days === 1 ? '' : 's'}`
}
const frequencyLabel = (value) => ({ weekly: 'Semanal', biweekly: 'Quincenal', monthly: 'Mensual' }[value] ?? value)

const vacationStatusMeta = (value) => ({
  pending: { label: 'Pendiente', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  approved: { label: 'Aprobada', class: 'bg-[#E9F8EE] text-[#187A31]' },
  rejected: { label: 'Rechazada', class: 'bg-[#FFE5E5] text-[#D70015]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })

const bonusStatusMeta = (value) => ({
  pending: { label: 'Pendiente', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  applied: { label: 'Aplicado', class: 'bg-[#E9F8EE] text-[#187A31]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })

const submitVacation = () => {
  vacationForm.post(`/employees/${props.employee.id}/vacations`, {
    preserveScroll: true,
    onSuccess: () => { showVacation.value = false; vacationForm.reset(); success.value = 'Solicitud de vacaciones registrada.' },
  })
}
const decideVacation = (vacation, status) => {
  router.patch(`/employees/${props.employee.id}/vacations/${vacation.id}/status`, { status }, {
    preserveScroll: true,
    onSuccess: () => { success.value = status === 'approved' ? 'Vacaciones aprobadas.' : 'Vacaciones rechazadas.' },
  })
}
const deleteVacation = (vacation) => {
  if (confirm('¿Eliminar esta solicitud de vacaciones?')) {
    router.delete(`/employees/${props.employee.id}/vacations/${vacation.id}`, { preserveScroll: true })
  }
}

const submitBonus = () => {
  bonusForm.post(`/employees/${props.employee.id}/bonuses`, {
    preserveScroll: true,
    onSuccess: () => { showBonus.value = false; bonusForm.reset(); success.value = 'Bono registrado.' },
  })
}
const deleteBonus = (bonus) => {
  if (confirm('¿Eliminar este bono?')) {
    router.delete(`/employees/${props.employee.id}/bonuses/${bonus.id}`, { preserveScroll: true })
  }
}

const submitLoan = () => {
  loanForm.post(`/employees/${props.employee.id}/loans`, {
    preserveScroll: true,
    onSuccess: () => { showLoan.value = false; loanForm.reset(); success.value = 'Préstamo registrado.' },
  })
}
const deleteLoan = (loan) => {
  if (confirm('¿Eliminar este préstamo?')) {
    router.delete(`/employees/${props.employee.id}/loans/${loan.id}`, { preserveScroll: true })
  }
}

const submitAbsence = () => {
  absenceForm.post(`/employees/${props.employee.id}/absences`, {
    preserveScroll: true,
    onSuccess: () => { showAbsence.value = false; absenceForm.reset(); success.value = 'Ausencia registrada.' },
  })
}
const deleteAbsence = (absence) => {
  router.delete(`/employees/${props.employee.id}/absences/${absence.id}`, { preserveScroll: true })
}
</script>

<template>
  <AppShell>
    <Head :title="employee.full_name" />
    <div class="mx-auto max-w-6xl">
      <Link href="/employees" class="mb-4 inline-flex items-center gap-1.5 text-xs font-semibold text-[#007AFF]"><ArrowLeft class="size-3.5" /> Volver a Personal</Link>

      <header class="mb-5 flex flex-col gap-4 rounded-2xl border border-[#E5E5E5] bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <div class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-[#007AFF] text-lg font-bold text-white">
            {{ employee.full_name.split(' ').slice(0, 2).map((p) => p[0]).join('').toUpperCase() }}
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="font-display text-xl font-bold text-[#1D1D1F]">{{ employee.full_name }}</h1>
              <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', employee.active ? 'bg-[#E9F8EE] text-[#187A31]' : 'bg-[#F2F2F7] text-[#6E6E73]']">{{ employee.active ? 'Activo' : 'Inactivo' }}</span>
            </div>
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-[#6E6E73]">
              <span class="flex items-center gap-1"><Briefcase class="size-3.5" />{{ employee.role?.name }}</span>
              <span v-if="employee.email" class="flex items-center gap-1"><Mail class="size-3.5" />{{ employee.email }}</span>
              <span v-if="employee.phone" class="flex items-center gap-1"><Phone class="size-3.5" />{{ employee.phone }}</span>
            </p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-xs text-[#8E8E93]">Sueldo base</p>
          <p class="text-lg font-bold text-[#1D1D1F]">{{ money(employee.base_salary) }}</p>
          <p class="text-xs text-[#8E8E93]">{{ frequencyLabel(employee.pay_frequency) }}</p>
        </div>
      </header>

      <div v-if="success" class="mb-4 flex items-center justify-between rounded-xl bg-[#E9F8EE] px-4 py-3 text-sm text-[#187A31]">
        <span class="flex items-center gap-2"><Check class="size-4" />{{ success }}</span>
        <button type="button" @click="success = ''"><X class="size-3.5" /></button>
      </div>

      <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Antigüedad</p>
          <p class="mt-1 text-lg font-bold text-[#1D1D1F]">{{ tenureLabel(tenureDays) }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Días trabajados (mes)</p>
          <p class="mt-1 text-lg font-bold text-[#007AFF]">{{ daysWorkedThisMonth }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Saldo de vacaciones</p>
          <p class="mt-1 text-lg font-bold text-[#AF52DE]">{{ vacationBalance }} días</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Préstamos activos</p>
          <p class="mt-1 text-lg font-bold text-[#D70015]">{{ money(activeLoanBalance) }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Bonos pendientes</p>
          <p class="mt-1 text-lg font-bold text-[#34C759]">{{ money(pendingBonusTotal) }}</p>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <!-- Vacaciones -->
        <section class="rounded-2xl border border-[#E5E5E5] bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-[#E5E5E5] p-4">
            <div class="flex items-center gap-2 text-sm font-semibold"><CalendarDays class="size-4 text-[#AF52DE]" /> Vacaciones</div>
            <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-[#007AFF]" @click="showVacation = true"><Plus class="size-3.5" /> Solicitar</button>
          </div>
          <div v-if="employee.vacations?.length" class="divide-y divide-[#EFEFF1]">
            <div v-for="vacation in employee.vacations" :key="vacation.id" class="flex items-center justify-between gap-3 p-3">
              <div>
                <p class="text-sm font-medium">{{ dateLabel(vacation.start_date) }} – {{ dateLabel(vacation.end_date) }}</p>
                <p class="text-xs text-[#8E8E93]">{{ vacation.days }} día(s){{ vacation.notes ? ` · ${vacation.notes}` : '' }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', vacationStatusMeta(vacation.status).class]">{{ vacationStatusMeta(vacation.status).label }}</span>
                <template v-if="vacation.status === 'pending'">
                  <button type="button" class="rounded-lg p-1 text-[#187A31] hover:bg-[#E9F8EE]" title="Aprobar" @click="decideVacation(vacation, 'approved')"><CheckCircle2 class="size-4" /></button>
                  <button type="button" class="rounded-lg p-1 text-[#D70015] hover:bg-[#FFE5E5]" title="Rechazar" @click="decideVacation(vacation, 'rejected')"><XCircle class="size-4" /></button>
                  <button type="button" class="rounded-lg p-1 text-[#8E8E93] hover:bg-[#F5F5F7]" title="Eliminar" @click="deleteVacation(vacation)"><Trash2 class="size-4" /></button>
                </template>
              </div>
            </div>
          </div>
          <p v-else class="p-4 text-center text-xs text-[#8E8E93]">Sin solicitudes de vacaciones.</p>
        </section>

        <!-- Bonos -->
        <section class="rounded-2xl border border-[#E5E5E5] bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-[#E5E5E5] p-4">
            <div class="flex items-center gap-2 text-sm font-semibold"><Banknote class="size-4 text-[#34C759]" /> Bonos</div>
            <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-[#007AFF]" @click="showBonus = true"><Plus class="size-3.5" /> Registrar</button>
          </div>
          <div v-if="employee.bonuses?.length" class="divide-y divide-[#EFEFF1]">
            <div v-for="bonus in employee.bonuses" :key="bonus.id" class="flex items-center justify-between gap-3 p-3">
              <div>
                <p class="text-sm font-medium">{{ bonus.concept }}</p>
                <p class="text-xs text-[#8E8E93]">{{ dateLabel(bonus.bonus_date) }} · {{ money(bonus.amount) }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', bonusStatusMeta(bonus.status).class]">{{ bonusStatusMeta(bonus.status).label }}</span>
                <button v-if="bonus.status === 'pending'" type="button" class="rounded-lg p-1 text-[#8E8E93] hover:bg-[#F5F5F7]" title="Eliminar" @click="deleteBonus(bonus)"><Trash2 class="size-4" /></button>
              </div>
            </div>
          </div>
          <p v-else class="p-4 text-center text-xs text-[#8E8E93]">Sin bonos registrados.</p>
        </section>

        <!-- Préstamos -->
        <section class="rounded-2xl border border-[#E5E5E5] bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-[#E5E5E5] p-4">
            <div class="flex items-center gap-2 text-sm font-semibold"><HandCoins class="size-4 text-[#D70015]" /> Préstamos</div>
            <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-[#007AFF]" @click="showLoan = true"><Plus class="size-3.5" /> Registrar</button>
          </div>
          <div v-if="employee.loans?.length" class="divide-y divide-[#EFEFF1]">
            <div v-for="loan in employee.loans" :key="loan.id" class="flex items-center justify-between gap-3 p-3">
              <div>
                <p class="text-sm font-medium">{{ money(loan.amount) }} <span class="text-xs font-normal text-[#8E8E93]">· cuota {{ money(loan.installment_amount) }}</span></p>
                <p class="text-xs text-[#8E8E93]">{{ dateLabel(loan.granted_at) }}{{ loan.reason ? ` · ${loan.reason}` : '' }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', loan.status === 'paid' ? 'bg-[#E9F8EE] text-[#187A31]' : 'bg-[#FFF6E5] text-[#B8860B]']">
                  {{ loan.status === 'paid' ? 'Pagado' : `Saldo ${money(loan.remaining_balance)}` }}
                </span>
                <button v-if="Number(loan.remaining_balance) === Number(loan.amount)" type="button" class="rounded-lg p-1 text-[#8E8E93] hover:bg-[#F5F5F7]" title="Eliminar" @click="deleteLoan(loan)"><Trash2 class="size-4" /></button>
              </div>
            </div>
          </div>
          <p v-else class="p-4 text-center text-xs text-[#8E8E93]">Sin préstamos registrados.</p>
        </section>

        <!-- Ausencias -->
        <section class="rounded-2xl border border-[#E5E5E5] bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-[#E5E5E5] p-4">
            <div class="flex items-center gap-2 text-sm font-semibold"><Clock class="size-4 text-[#8E8E93]" /> Ausencias recientes</div>
            <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-[#007AFF]" @click="showAbsence = true"><Plus class="size-3.5" /> Registrar</button>
          </div>
          <div v-if="employee.absences?.length" class="divide-y divide-[#EFEFF1]">
            <div v-for="absence in employee.absences" :key="absence.id" class="flex items-center justify-between gap-3 p-3">
              <div>
                <p class="text-sm font-medium">{{ dateLabel(absence.date) }}</p>
                <p class="text-xs text-[#8E8E93]">{{ absence.notes || 'Sin detalle' }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', absence.type === 'justified' ? 'bg-[#F1F6FF] text-[#245DA8]' : 'bg-[#FFE5E5] text-[#D70015]']">{{ absence.type === 'justified' ? 'Justificada' : 'Injustificada' }}</span>
                <button type="button" class="rounded-lg p-1 text-[#8E8E93] hover:bg-[#F5F5F7]" title="Eliminar" @click="deleteAbsence(absence)"><Trash2 class="size-4" /></button>
              </div>
            </div>
          </div>
          <p v-else class="p-4 text-center text-xs text-[#8E8E93]">Sin ausencias registradas.</p>
        </section>
      </div>

      <!-- Historial de planilla -->
      <section class="mt-4 overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-[#E5E5E5] p-4 text-sm font-semibold"><Wallet class="size-4 text-[#007AFF]" /> Historial de planilla</div>
        <div v-if="employee.payroll_items?.length" class="divide-y divide-[#EFEFF1]">
          <Link v-for="item in employee.payroll_items" :key="item.id" :href="`/payroll/${item.period.id}`" class="flex items-center justify-between gap-3 p-3 hover:bg-[#F8FAFF]">
            <div>
              <p class="text-sm font-medium">{{ item.period.code }}</p>
              <p class="text-xs text-[#8E8E93]">{{ dateLabel(item.period.period_start) }} – {{ dateLabel(item.period.period_end) }}</p>
            </div>
            <p class="text-sm font-semibold text-[#187A31]">{{ money(item.net_pay) }}</p>
          </Link>
        </div>
        <p v-else class="p-4 text-center text-xs text-[#8E8E93]">Sin planillas generadas todavía.</p>
      </section>
    </div>

    <!-- Modal: Solicitar vacaciones -->
    <div v-if="showVacation" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showVacation = false">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitVacation">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#F8F2FC] text-[#AF52DE]"><CalendarDays class="size-5" /></div><div><h2 class="text-lg font-bold">Solicitar vacaciones</h2><p class="text-xs text-[#8E8E93]">Saldo disponible: {{ vacationBalance }} días.</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showVacation = false"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Inicio</label><input v-model="vacationForm.start_date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="vacationForm.errors.start_date" class="mt-1 text-xs text-[#D70015]">{{ vacationForm.errors.start_date }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Fin</label><input v-model="vacationForm.end_date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="vacationForm.errors.end_date" class="mt-1 text-xs text-[#D70015]">{{ vacationForm.errors.end_date }}</p></div>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="vacationForm.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4"><button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showVacation = false">Cancelar</button><button type="submit" :disabled="vacationForm.processing" class="h-10 rounded-xl bg-[#AF52DE] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ vacationForm.processing ? 'Guardando...' : 'Solicitar' }}</button></footer>
      </form>
    </div>

    <!-- Modal: Registrar bono -->
    <div v-if="showBonus" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showBonus = false">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitBonus">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#E9F8EE] text-[#34C759]"><Banknote class="size-5" /></div><div><h2 class="text-lg font-bold">Registrar bono</h2><p class="text-xs text-[#8E8E93]">Se sumará al bruto de la próxima planilla.</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showBonus = false"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div><label class="mb-1 block text-xs font-semibold">Concepto</label><input v-model="bonusForm.concept" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm" placeholder="Ej. Bono de productividad"><p v-if="bonusForm.errors.concept" class="mt-1 text-xs text-[#D70015]">{{ bonusForm.errors.concept }}</p></div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Monto</label><input v-model="bonusForm.amount" type="number" step="0.01" min="0" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="bonusForm.errors.amount" class="mt-1 text-xs text-[#D70015]">{{ bonusForm.errors.amount }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Fecha</label><input v-model="bonusForm.bonus_date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"></div>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="bonusForm.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4"><button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showBonus = false">Cancelar</button><button type="submit" :disabled="bonusForm.processing" class="h-10 rounded-xl bg-[#34C759] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ bonusForm.processing ? 'Guardando...' : 'Registrar' }}</button></footer>
      </form>
    </div>

    <!-- Modal: Registrar préstamo -->
    <div v-if="showLoan" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showLoan = false">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitLoan">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#FFE5E5] text-[#D70015]"><HandCoins class="size-5" /></div><div><h2 class="text-lg font-bold">Registrar préstamo</h2><p class="text-xs text-[#8E8E93]">La cuota se descontará del neto en cada planilla.</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showLoan = false"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Monto total</label><input v-model="loanForm.amount" type="number" step="0.01" min="0" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="loanForm.errors.amount" class="mt-1 text-xs text-[#D70015]">{{ loanForm.errors.amount }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Cuota por planilla</label><input v-model="loanForm.installment_amount" type="number" step="0.01" min="0" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="loanForm.errors.installment_amount" class="mt-1 text-xs text-[#D70015]">{{ loanForm.errors.installment_amount }}</p></div>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Fecha de entrega</label><input v-model="loanForm.granted_at" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"></div>
          <div><label class="mb-1 block text-xs font-semibold">Motivo <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="loanForm.reason" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4"><button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showLoan = false">Cancelar</button><button type="submit" :disabled="loanForm.processing" class="h-10 rounded-xl bg-[#D70015] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ loanForm.processing ? 'Guardando...' : 'Registrar' }}</button></footer>
      </form>
    </div>

    <!-- Modal: Registrar ausencia -->
    <div v-if="showAbsence" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showAbsence = false">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitAbsence">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#F5F5F7] text-[#6E6E73]"><Clock class="size-5" /></div><div><h2 class="text-lg font-bold">Registrar ausencia</h2><p class="text-xs text-[#8E8E93]">Afecta los días trabajados del periodo.</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showAbsence = false"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Fecha</label><input v-model="absenceForm.date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="absenceForm.errors.date" class="mt-1 text-xs text-[#D70015]">{{ absenceForm.errors.date }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Tipo</label><select v-model="absenceForm.type" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><option value="unjustified">Injustificada</option><option value="justified">Justificada</option></select></div>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="absenceForm.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4"><button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showAbsence = false">Cancelar</button><button type="submit" :disabled="absenceForm.processing" class="h-10 rounded-xl bg-[#1D1D1F] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ absenceForm.processing ? 'Guardando...' : 'Registrar' }}</button></footer>
      </form>
    </div>
  </AppShell>
</template>
