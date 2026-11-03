<script setup>
import { Head, Link } from '@inertiajs/vue3'
import {
  ArrowLeft, Banknote, CalendarDays, FileText, Gift, HandCoins, MinusCircle, Printer, UserMinus, UserRound, Wallet,
} from '@lucide/vue'
import AppShell from '../../../Components/AppShell.vue'

const props = defineProps({ employee: Object, aguinaldoItems: Array })

const leaveTypeLabels = {
  sick: 'Enfermedad',
  maternity: 'Maternidad',
  paternity: 'Paternidad',
  bereavement: 'Duelo / fallecimiento familiar',
  marriage: 'Matrimonio',
  legal: 'Trámites legales',
  unpaid_personal: 'Personal sin goce de salario',
  other: 'Otro',
}
const terminationTypeLabels = {
  unjustified_dismissal: 'Despido sin causa',
  resignation: 'Renuncia voluntaria',
  justified_dismissal: 'Despido con causa justificada',
  mutual_agreement: 'Mutuo acuerdo',
}

const money = (value) => new Intl.NumberFormat('es-NI', { style: 'currency', currency: 'NIO' }).format(value ?? 0)
const dateLabel = (value) => value
  ? new Intl.DateTimeFormat('es-NI', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
  : '—'

const statusLabel = (map, value) => map[value] ?? value
const vacationStatus = { pending: 'Pendiente', approved: 'Aprobada', rejected: 'Rechazada' }
const bonusStatus = { pending: 'Pendiente', applied: 'Aplicado' }
const deductionStatus = { pending: 'Pendiente', applied: 'Aplicada' }
const loanStatus = { active: 'Activo', paid: 'Pagado' }
const periodStatus = { draft: 'Borrador', approved: 'Aprobada', paid: 'Pagada' }
const settlementStatus = { draft: 'Borrador', approved: 'Aprobada', paid: 'Pagada' }

const print = () => window.print()
</script>

<template>
  <AppShell>
    <Head :title="`Historial · ${employee.full_name}`" />
    <div class="mx-auto max-w-5xl">
      <Link href="/payroll" class="mb-4 inline-flex items-center gap-1.5 text-xs font-semibold text-[#007AFF] print:hidden"><ArrowLeft class="size-3.5" /> Volver a Nómina</Link>

      <header class="mb-5 flex flex-col gap-4 rounded-2xl border border-[#E5E5E5] bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between print:border-0 print:shadow-none">
        <div class="flex items-center gap-3">
          <div class="flex size-12 items-center justify-center rounded-xl bg-[#007AFF]/10 text-[#007AFF]"><UserRound class="size-6" /></div>
          <div>
            <h1 class="font-display text-xl font-bold text-[#1D1D1F]">{{ employee.full_name }}</h1>
            <p class="mt-1 text-xs text-[#6E6E73]">{{ employee.identity_number || 'Sin identificación' }} · {{ employee.role?.name }} · {{ employee.active ? 'Activo' : 'Inactivo' }}</p>
            <p class="mt-1 text-xs text-[#6E6E73]">Ingreso {{ dateLabel(employee.hired_at) }} · Sueldo base {{ money(employee.base_salary) }}</p>
          </div>
        </div>
        <button type="button" class="inline-flex h-10 items-center gap-2 self-start rounded-xl border border-[#E5E5E5] px-4 text-sm font-semibold text-[#6E6E73] hover:bg-[#F5F5F7] print:hidden" @click="print"><Printer class="size-4" /> Imprimir</button>
      </header>

      <!-- Planillas -->
      <section class="mb-4 overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm print:border-0 print:shadow-none print:break-inside-avoid">
        <div class="flex items-center gap-2 border-b border-[#E5E5E5] p-4"><Wallet class="size-4 text-[#007AFF]" /><h2 class="text-sm font-bold">Planillas</h2></div>
        <div class="overflow-x-auto print:overflow-visible">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-[#E5E5E5] bg-[#FAFAFB] text-left text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">
                <th class="px-4 py-2">Planilla</th><th class="px-4 py-2">Periodo</th><th class="px-4 py-2 text-right">Bruto</th><th class="px-4 py-2 text-right">Neto</th><th class="px-4 py-2">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EFEFF1]">
              <tr v-for="item in employee.payroll_items" :key="item.id">
                <td class="px-4 py-2 font-medium">{{ item.period?.code }}</td>
                <td class="px-4 py-2 text-[#6E6E73]">{{ dateLabel(item.period?.period_start) }} – {{ dateLabel(item.period?.period_end) }}</td>
                <td class="px-4 py-2 text-right">{{ money(item.gross_salary) }}</td>
                <td class="px-4 py-2 text-right font-semibold text-[#187A31]">{{ money(item.net_pay) }}</td>
                <td class="px-4 py-2 text-[#6E6E73]">{{ statusLabel(periodStatus, item.period?.status) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!employee.payroll_items?.length" class="p-4 text-xs text-[#8E8E93]">Sin planillas registradas.</p>
      </section>

      <!-- Aguinaldo -->
      <section class="mb-4 overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm print:border-0 print:shadow-none print:break-inside-avoid">
        <div class="flex items-center gap-2 border-b border-[#E5E5E5] p-4"><Gift class="size-4 text-[#007AFF]" /><h2 class="text-sm font-bold">Aguinaldo</h2></div>
        <div class="overflow-x-auto print:overflow-visible">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-[#E5E5E5] bg-[#FAFAFB] text-left text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">
                <th class="px-4 py-2">Periodo</th><th class="px-4 py-2 text-right">Días</th><th class="px-4 py-2 text-right">Monto</th><th class="px-4 py-2">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EFEFF1]">
              <tr v-for="item in aguinaldoItems" :key="item.id">
                <td class="px-4 py-2 font-medium">{{ item.period?.code }} · {{ item.period?.year }}</td>
                <td class="px-4 py-2 text-right text-[#6E6E73]">{{ item.days_employed }} / 360</td>
                <td class="px-4 py-2 text-right font-semibold text-[#187A31]">{{ money(item.amount) }}</td>
                <td class="px-4 py-2 text-[#6E6E73]">{{ statusLabel(periodStatus, item.period?.status) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!aguinaldoItems?.length" class="p-4 text-xs text-[#8E8E93]">Sin aguinaldos registrados.</p>
      </section>

      <!-- Vacaciones -->
      <section class="mb-4 overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm print:border-0 print:shadow-none print:break-inside-avoid">
        <div class="flex items-center gap-2 border-b border-[#E5E5E5] p-4"><CalendarDays class="size-4 text-[#AF52DE]" /><h2 class="text-sm font-bold">Vacaciones</h2></div>
        <div class="divide-y divide-[#EFEFF1]">
          <div v-for="vacation in employee.vacations" :key="vacation.id" class="flex items-center justify-between px-4 py-2 text-sm">
            <span>{{ dateLabel(vacation.start_date) }} – {{ dateLabel(vacation.end_date) }} · {{ vacation.days }} día(s) · {{ vacation.paid ? 'con goce' : 'sin goce' }}</span>
            <span class="text-xs text-[#6E6E73]">{{ statusLabel(vacationStatus, vacation.status) }}</span>
          </div>
        </div>
        <p v-if="!employee.vacations?.length" class="p-4 text-xs text-[#8E8E93]">Sin vacaciones registradas.</p>
      </section>

      <!-- Permisos -->
      <section class="mb-4 overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm print:border-0 print:shadow-none print:break-inside-avoid">
        <div class="flex items-center gap-2 border-b border-[#E5E5E5] p-4"><FileText class="size-4 text-[#007AFF]" /><h2 class="text-sm font-bold">Permisos</h2></div>
        <div class="divide-y divide-[#EFEFF1]">
          <div v-for="leave in employee.leaves" :key="leave.id" class="flex items-center justify-between px-4 py-2 text-sm">
            <span>{{ leaveTypeLabels[leave.type] ?? leave.type }} · {{ dateLabel(leave.start_date) }} – {{ dateLabel(leave.end_date) }} · {{ leave.days }} día(s) · {{ leave.paid ? 'con goce' : 'sin goce' }}</span>
            <span class="text-xs text-[#6E6E73]">{{ statusLabel(vacationStatus, leave.status) }}</span>
          </div>
        </div>
        <p v-if="!employee.leaves?.length" class="p-4 text-xs text-[#8E8E93]">Sin permisos registrados.</p>
      </section>

      <!-- Bonos -->
      <section class="mb-4 overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm print:border-0 print:shadow-none print:break-inside-avoid">
        <div class="flex items-center gap-2 border-b border-[#E5E5E5] p-4"><Banknote class="size-4 text-[#34C759]" /><h2 class="text-sm font-bold">Bonos</h2></div>
        <div class="divide-y divide-[#EFEFF1]">
          <div v-for="bonus in employee.bonuses" :key="bonus.id" class="flex items-center justify-between px-4 py-2 text-sm">
            <span>{{ bonus.concept }} · {{ dateLabel(bonus.bonus_date) }}</span>
            <span class="flex items-center gap-2"><span class="font-semibold text-[#34C759]">{{ money(bonus.amount) }}</span><span class="text-xs text-[#6E6E73]">{{ statusLabel(bonusStatus, bonus.status) }}</span></span>
          </div>
        </div>
        <p v-if="!employee.bonuses?.length" class="p-4 text-xs text-[#8E8E93]">Sin bonos registrados.</p>
      </section>

      <!-- Deducciones -->
      <section class="mb-4 overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm print:border-0 print:shadow-none print:break-inside-avoid">
        <div class="flex items-center gap-2 border-b border-[#E5E5E5] p-4"><MinusCircle class="size-4 text-[#FF9500]" /><h2 class="text-sm font-bold">Deducciones</h2></div>
        <div class="divide-y divide-[#EFEFF1]">
          <div v-for="deduction in employee.deductions" :key="deduction.id" class="flex items-center justify-between px-4 py-2 text-sm">
            <span>{{ deduction.concept }} · {{ dateLabel(deduction.deduction_date) }}</span>
            <span class="flex items-center gap-2"><span class="font-semibold text-[#D70015]">-{{ money(deduction.amount) }}</span><span class="text-xs text-[#6E6E73]">{{ statusLabel(deductionStatus, deduction.status) }}</span></span>
          </div>
        </div>
        <p v-if="!employee.deductions?.length" class="p-4 text-xs text-[#8E8E93]">Sin deducciones registradas.</p>
      </section>

      <!-- Préstamos -->
      <section class="mb-4 overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm print:border-0 print:shadow-none print:break-inside-avoid">
        <div class="flex items-center gap-2 border-b border-[#E5E5E5] p-4"><HandCoins class="size-4 text-[#D70015]" /><h2 class="text-sm font-bold">Préstamos</h2></div>
        <div class="divide-y divide-[#EFEFF1]">
          <div v-for="loan in employee.loans" :key="loan.id" class="flex items-center justify-between px-4 py-2 text-sm">
            <span>{{ money(loan.amount) }} otorgado el {{ dateLabel(loan.granted_at) }} · cuota {{ money(loan.installment_amount) }}</span>
            <span class="flex items-center gap-2"><span class="font-semibold">{{ money(loan.remaining_balance) }} pendiente</span><span class="text-xs text-[#6E6E73]">{{ statusLabel(loanStatus, loan.status) }}</span></span>
          </div>
        </div>
        <p v-if="!employee.loans?.length" class="p-4 text-xs text-[#8E8E93]">Sin préstamos registrados.</p>
      </section>

      <!-- Liquidación -->
      <section v-if="employee.settlements?.length" class="mb-4 overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm print:border-0 print:shadow-none print:break-inside-avoid">
        <div class="flex items-center gap-2 border-b border-[#E5E5E5] p-4"><UserMinus class="size-4 text-[#D70015]" /><h2 class="text-sm font-bold">Liquidación</h2></div>
        <div class="divide-y divide-[#EFEFF1]">
          <Link v-for="settlement in employee.settlements" :key="settlement.id" :href="`/settlements/${settlement.id}`" class="flex items-center justify-between px-4 py-2 text-sm hover:bg-[#F8FAFF] print:pointer-events-none">
            <span>{{ settlement.code }} · {{ terminationTypeLabels[settlement.termination_type] ?? settlement.termination_type }} · Salida {{ dateLabel(settlement.termination_date) }}</span>
            <span class="flex items-center gap-2"><span class="font-semibold text-[#187A31]">{{ money(settlement.net_amount) }}</span><span class="text-xs text-[#6E6E73]">{{ statusLabel(settlementStatus, settlement.status) }}</span></span>
          </Link>
        </div>
      </section>
    </div>
  </AppShell>
</template>
