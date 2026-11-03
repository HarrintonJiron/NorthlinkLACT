<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import {
  AlertTriangle, Banknote, Calculator, CalendarDays, CalendarRange, Check, CheckCircle2, ChevronRight, Clock,
  FileText, Gift, HandCoins, MinusCircle, Pencil, Percent, Plus, Printer, Search, ShieldCheck, Trash2, UserMinus, UserRound, UsersRound, Wallet, X, XCircle,
} from '@lucide/vue'
import AppShell from '../../../Components/AppShell.vue'

const props = defineProps({
  periods: Object,
  vacations: Array,
  bonuses: Array,
  loans: Array,
  leaves: Array,
  deductions: Array,
  aguinaldos: Array,
  settlements: Array,
  taxPolicies: Array,
  employees: Array,
  stats: Object,
  currentYear: Number,
})

const tab = ref('planillas')
const exportableSections = ['planillas', 'aguinaldo', 'vacations', 'leaves', 'bonuses', 'deductions', 'loans', 'settlements']
const exportRange = ref('month')
const exportDate = ref(new Date().toISOString().slice(0, 10))
const exportStart = ref('')
const exportEnd = ref('')
const exportEmployeeId = ref('')
const exportUrl = computed(() => {
  const params = new URLSearchParams()
  params.set('range', exportRange.value)
  if (exportRange.value === 'custom') {
    params.set('start', exportStart.value)
    params.set('end', exportEnd.value)
  } else {
    params.set('date', exportDate.value)
  }
  if (exportEmployeeId.value) params.set('employee_id', exportEmployeeId.value)
  return `/payroll-export/${tab.value}?${params.toString()}`
})
const exportReady = computed(() => exportRange.value !== 'custom' || (exportStart.value && exportEnd.value))
const showGenerate = ref(false)
const showGenerateAguinaldo = ref(false)
const showTaxPolicyCreate = ref(false)
const success = ref('')
const search = ref('')
const employeeFilter = ref('all')
const statusFilter = ref('all')

const tabs = [
  { key: 'planillas', label: 'Planillas', icon: Wallet },
  { key: 'aguinaldo', label: 'Aguinaldo', icon: Gift },
  { key: 'vacations', label: 'Vacaciones', icon: CalendarDays },
  { key: 'leaves', label: 'Permisos', icon: FileText },
  { key: 'bonuses', label: 'Bonos', icon: Banknote },
  { key: 'deductions', label: 'Deducciones', icon: MinusCircle },
  { key: 'loans', label: 'Préstamos', icon: HandCoins },
  { key: 'settlements', label: 'Liquidaciones', icon: UserMinus },
  { key: 'taxes', label: 'Impuestos', icon: Percent },
]

const terminationTypeLabels = {
  unjustified_dismissal: 'Despido sin causa',
  resignation: 'Renuncia voluntaria',
  justified_dismissal: 'Despido con causa justificada',
  mutual_agreement: 'Mutuo acuerdo',
}
const terminationTypeLabel = (value) => terminationTypeLabels[value] ?? value

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
const leaveTypes = [
  { value: 'sick', label: 'Enfermedad', paid: true },
  { value: 'maternity', label: 'Maternidad', paid: true },
  { value: 'paternity', label: 'Paternidad', paid: true },
  { value: 'bereavement', label: 'Duelo / fallecimiento familiar', paid: true },
  { value: 'marriage', label: 'Matrimonio', paid: true },
  { value: 'legal', label: 'Trámites legales', paid: false },
  { value: 'unpaid_personal', label: 'Personal sin goce de salario', paid: false },
  { value: 'other', label: 'Otro', paid: false },
]

const showSettlementCreate = ref(false)
const settlementForm = useForm({
  employee_id: '',
  termination_type: 'unjustified_dismissal',
  termination_date: '',
  pending_salary_start: '',
  severance_method: 'legal',
  severance_amount: '',
  notes: '',
})
const openSettlementCreate = () => {
  settlementForm.reset()
  settlementForm.clearErrors()
  showSettlementCreate.value = true
}
const closeSettlementCreate = () => {
  if (!settlementForm.processing) {
    showSettlementCreate.value = false
  }
}
const onSettlementTerminationDateChange = () => {
  if (settlementForm.termination_date && !settlementForm.pending_salary_start) {
    settlementForm.pending_salary_start = `${settlementForm.termination_date.slice(0, 7)}-01`
  }
}
const submitSettlementCreate = () => {
  settlementForm.post('/settlements', {
    preserveScroll: true,
    onSuccess: () => { showSettlementCreate.value = false; settlementForm.reset() },
  })
}

const showVacationCreate = ref(false)
const showLeaveCreate = ref(false)
const showBonusCreate = ref(false)
const showDeductionCreate = ref(false)
const showLoanCreate = ref(false)

const editingVacation = ref(null)
const editingLeave = ref(null)
const editingBonus = ref(null)
const editingDeduction = ref(null)
const editingLoan = ref(null)

const vacationForm = useForm({ employee_id: '', start_date: '', end_date: '', paid: true, notes: '' })
const leaveForm = useForm({ employee_id: '', type: 'sick', start_date: '', end_date: '', paid: true, notes: '' })
const bonusForm = useForm({ employee_id: '', concept: '', amount: '', bonus_date: new Date().toISOString().slice(0, 10), notes: '' })
const deductionForm = useForm({ employee_id: '', concept: '', amount: '', deduction_date: new Date().toISOString().slice(0, 10), notes: '' })
const loanForm = useForm({ employee_id: '', amount: '', installment_amount: '', granted_at: new Date().toISOString().slice(0, 10), reason: '' })

const onLeaveTypeChange = () => {
  const match = leaveTypes.find((t) => t.value === leaveForm.type)
  leaveForm.paid = match?.paid ?? false
}

const openVacationCreate = () => { vacationForm.reset(); vacationForm.clearErrors(); editingVacation.value = null; showVacationCreate.value = true }
const openVacationEdit = (vacation) => {
  vacationForm.reset()
  vacationForm.clearErrors()
  editingVacation.value = vacation
  vacationForm.employee_id = vacation.employee_id
  vacationForm.start_date = vacation.start_date?.slice(0, 10) ?? ''
  vacationForm.end_date = vacation.end_date?.slice(0, 10) ?? ''
  vacationForm.paid = vacation.paid
  vacationForm.notes = vacation.notes ?? ''
  showVacationCreate.value = true
}
const submitVacationCreate = () => {
  const isEditing = !!editingVacation.value
  const options = {
    preserveScroll: true,
    onSuccess: () => { showVacationCreate.value = false; vacationForm.reset(); editingVacation.value = null; success.value = isEditing ? 'Solicitud actualizada.' : 'Solicitud de vacaciones registrada.' },
  }
  if (isEditing) {
    vacationForm.put(`/employees/${vacationForm.employee_id}/vacations/${editingVacation.value.id}`, options)
  } else {
    vacationForm.post(`/employees/${vacationForm.employee_id}/vacations`, options)
  }
}
const decideVacation = (vacation, status) => {
  router.patch(`/employees/${vacation.employee_id}/vacations/${vacation.id}/status`, { status }, {
    preserveScroll: true,
    onSuccess: () => { success.value = status === 'approved' ? 'Vacaciones aprobadas.' : 'Vacaciones rechazadas.' },
  })
}
const deleteVacation = (vacation) => {
  if (confirm('¿Eliminar esta solicitud de vacaciones?')) {
    router.delete(`/employees/${vacation.employee_id}/vacations/${vacation.id}`, { preserveScroll: true })
  }
}

const openLeaveCreate = () => { leaveForm.reset(); leaveForm.clearErrors(); editingLeave.value = null; onLeaveTypeChange(); showLeaveCreate.value = true }
const openLeaveEdit = (leave) => {
  leaveForm.reset()
  leaveForm.clearErrors()
  editingLeave.value = leave
  leaveForm.employee_id = leave.employee_id
  leaveForm.type = leave.type
  leaveForm.start_date = leave.start_date?.slice(0, 10) ?? ''
  leaveForm.end_date = leave.end_date?.slice(0, 10) ?? ''
  leaveForm.paid = leave.paid
  leaveForm.notes = leave.notes ?? ''
  showLeaveCreate.value = true
}
const submitLeaveCreate = () => {
  const isEditing = !!editingLeave.value
  const options = {
    preserveScroll: true,
    onSuccess: () => { showLeaveCreate.value = false; leaveForm.reset(); editingLeave.value = null; success.value = isEditing ? 'Permiso actualizado.' : 'Solicitud de permiso registrada.' },
  }
  if (isEditing) {
    leaveForm.put(`/employees/${leaveForm.employee_id}/leaves/${editingLeave.value.id}`, options)
  } else {
    leaveForm.post(`/employees/${leaveForm.employee_id}/leaves`, options)
  }
}
const decideLeave = (leave, status) => {
  router.patch(`/employees/${leave.employee_id}/leaves/${leave.id}/status`, { status }, {
    preserveScroll: true,
    onSuccess: () => { success.value = status === 'approved' ? 'Permiso aprobado.' : 'Permiso rechazado.' },
  })
}
const deleteLeave = (leave) => {
  if (confirm('¿Eliminar esta solicitud de permiso?')) {
    router.delete(`/employees/${leave.employee_id}/leaves/${leave.id}`, { preserveScroll: true })
  }
}

const openBonusCreate = () => { bonusForm.reset(); bonusForm.clearErrors(); editingBonus.value = null; showBonusCreate.value = true }
const openBonusEdit = (bonus) => {
  bonusForm.reset()
  bonusForm.clearErrors()
  editingBonus.value = bonus
  bonusForm.employee_id = bonus.employee_id
  bonusForm.concept = bonus.concept
  bonusForm.amount = bonus.amount
  bonusForm.bonus_date = bonus.bonus_date?.slice(0, 10) ?? ''
  bonusForm.notes = bonus.notes ?? ''
  showBonusCreate.value = true
}
const submitBonusCreate = () => {
  const isEditing = !!editingBonus.value
  const options = {
    preserveScroll: true,
    onSuccess: () => { showBonusCreate.value = false; bonusForm.reset(); editingBonus.value = null; success.value = isEditing ? 'Bono actualizado.' : 'Bono registrado.' },
  }
  if (isEditing) {
    bonusForm.put(`/employees/${bonusForm.employee_id}/bonuses/${editingBonus.value.id}`, options)
  } else {
    bonusForm.post(`/employees/${bonusForm.employee_id}/bonuses`, options)
  }
}
const deleteBonus = (bonus) => {
  if (confirm('¿Eliminar este bono?')) {
    router.delete(`/employees/${bonus.employee_id}/bonuses/${bonus.id}`, { preserveScroll: true })
  }
}

const openDeductionCreate = () => { deductionForm.reset(); deductionForm.clearErrors(); editingDeduction.value = null; showDeductionCreate.value = true }
const openDeductionEdit = (deduction) => {
  deductionForm.reset()
  deductionForm.clearErrors()
  editingDeduction.value = deduction
  deductionForm.employee_id = deduction.employee_id
  deductionForm.concept = deduction.concept
  deductionForm.amount = deduction.amount
  deductionForm.deduction_date = deduction.deduction_date?.slice(0, 10) ?? ''
  deductionForm.notes = deduction.notes ?? ''
  showDeductionCreate.value = true
}
const submitDeductionCreate = () => {
  const isEditing = !!editingDeduction.value
  const options = {
    preserveScroll: true,
    onSuccess: () => { showDeductionCreate.value = false; deductionForm.reset(); editingDeduction.value = null; success.value = isEditing ? 'Deducción actualizada.' : 'Deducción registrada.' },
  }
  if (isEditing) {
    deductionForm.put(`/employees/${deductionForm.employee_id}/deductions/${editingDeduction.value.id}`, options)
  } else {
    deductionForm.post(`/employees/${deductionForm.employee_id}/deductions`, options)
  }
}
const deleteDeduction = (deduction) => {
  if (confirm('¿Eliminar esta deducción?')) {
    router.delete(`/employees/${deduction.employee_id}/deductions/${deduction.id}`, { preserveScroll: true })
  }
}

const openLoanCreate = () => { loanForm.reset(); loanForm.clearErrors(); editingLoan.value = null; showLoanCreate.value = true }
const openLoanEdit = (loan) => {
  loanForm.reset()
  loanForm.clearErrors()
  editingLoan.value = loan
  loanForm.employee_id = loan.employee_id
  loanForm.amount = loan.amount
  loanForm.installment_amount = loan.installment_amount
  loanForm.granted_at = loan.granted_at?.slice(0, 10) ?? ''
  loanForm.reason = loan.reason ?? ''
  showLoanCreate.value = true
}
const submitLoanCreate = () => {
  const isEditing = !!editingLoan.value
  const options = {
    preserveScroll: true,
    onSuccess: () => { showLoanCreate.value = false; loanForm.reset(); editingLoan.value = null; success.value = isEditing ? 'Préstamo actualizado.' : 'Préstamo registrado.' },
  }
  if (isEditing) {
    loanForm.put(`/employees/${loanForm.employee_id}/loans/${editingLoan.value.id}`, options)
  } else {
    loanForm.post(`/employees/${loanForm.employee_id}/loans`, options)
  }
}
const deleteLoan = (loan) => {
  if (confirm('¿Eliminar este préstamo?')) {
    router.delete(`/employees/${loan.employee_id}/loans/${loan.id}`, { preserveScroll: true })
  }
}

const switchTab = (key) => {
  tab.value = key
  statusFilter.value = 'all'
}

const statusOptions = computed(() => ({
  vacations: [
    { value: 'all', label: 'Todos los estados' },
    { value: 'pending', label: 'Pendiente' },
    { value: 'approved', label: 'Aprobada' },
    { value: 'rejected', label: 'Rechazada' },
  ],
  bonuses: [
    { value: 'all', label: 'Todos los estados' },
    { value: 'pending', label: 'Pendiente' },
    { value: 'applied', label: 'Aplicado' },
  ],
  loans: [
    { value: 'all', label: 'Todos los estados' },
    { value: 'active', label: 'Activo' },
    { value: 'paid', label: 'Pagado' },
  ],
  leaves: [
    { value: 'all', label: 'Todos los estados' },
    { value: 'pending', label: 'Pendiente' },
    { value: 'approved', label: 'Aprobado' },
    { value: 'rejected', label: 'Rechazado' },
  ],
  deductions: [
    { value: 'all', label: 'Todos los estados' },
    { value: 'pending', label: 'Pendiente' },
    { value: 'applied', label: 'Aplicada' },
  ],
}[tab.value] ?? []))

const form = useForm({
  pay_frequency: 'monthly',
  period_start: '',
  period_end: '',
  notes: '',
  confirm_duplicate: false,
})

const aguinaldoForm = useForm({
  year: props.currentYear,
  notes: '',
})

const currentTaxPolicy = computed(() => props.taxPolicies[0] ?? null)
const activeTaxPolicy = computed(() => {
  const today = new Date().toISOString().slice(0, 10)
  return props.taxPolicies.find((p) => p.effective_from.slice(0, 10) <= today) ?? null
})

const alerts = computed(() => {
  const stats = props.stats
  const items = []
  if (!activeTaxPolicy.value) {
    items.push({ label: 'Sin política de impuestos vigente', tab: 'taxes', tone: 'danger' })
  }
  if (stats.draft > 0) {
    items.push({ label: `${stats.draft} planilla(s) en borrador`, tab: 'planillas', tone: 'warn' })
  }
  if (stats.approved > 0) {
    items.push({ label: `${stats.approved} planilla(s) aprobada(s) por pagar`, tab: 'planillas', tone: 'warn' })
  }
  if (stats.pending_vacations > 0) {
    items.push({ label: `${stats.pending_vacations} vacación(es) por aprobar`, tab: 'vacations', tone: 'warn' })
  }
  if (stats.pending_leaves > 0) {
    items.push({ label: `${stats.pending_leaves} permiso(s) por aprobar`, tab: 'leaves', tone: 'warn' })
  }
  if (stats.pending_bonuses > 0) {
    items.push({ label: `${stats.pending_bonuses} bono(s) pendiente(s) · ${money(stats.pending_bonus_total)}`, tab: 'bonuses', tone: 'info' })
  }
  if (stats.pending_deductions > 0) {
    items.push({ label: `${stats.pending_deductions} deducción(es) pendiente(s) · ${money(stats.pending_deduction_total)}`, tab: 'deductions', tone: 'info' })
  }
  if (stats.active_loans > 0) {
    items.push({ label: `${stats.active_loans} préstamo(s) activo(s) · ${money(stats.active_loan_balance)} en saldo`, tab: 'loans', tone: 'info' })
  }
  if (stats.settlements_draft > 0) {
    items.push({ label: `${stats.settlements_draft} liquidación(es) en borrador`, tab: 'settlements', tone: 'warn' })
  }
  if (stats.settlements_approved > 0) {
    items.push({ label: `${stats.settlements_approved} liquidación(es) aprobada(s) por pagar`, tab: 'settlements', tone: 'warn' })
  }
  return items
})

const taxPolicyForm = useForm({
  name: '',
  effective_from: new Date().toISOString().slice(0, 10),
  inss_employee_rate: currentTaxPolicy.value ? Number(currentTaxPolicy.value.inss_employee_rate) * 100 : 7,
  inss_employer_rate: currentTaxPolicy.value ? Number(currentTaxPolicy.value.inss_employer_rate) * 100 : 21.5,
  inatec_rate: currentTaxPolicy.value ? Number(currentTaxPolicy.value.inatec_rate) * 100 : 2,
  ir_threshold_1: currentTaxPolicy.value?.ir_brackets?.[0]?.threshold ?? 100000,
  ir_threshold_2: currentTaxPolicy.value?.ir_brackets?.[1]?.threshold ?? 200000,
  ir_threshold_3: currentTaxPolicy.value?.ir_brackets?.[2]?.threshold ?? 350000,
  ir_threshold_4: currentTaxPolicy.value?.ir_brackets?.[3]?.threshold ?? 500000,
  ir_rate_1: currentTaxPolicy.value ? Number(currentTaxPolicy.value.ir_brackets?.[0]?.rate ?? 0) * 100 : 0,
  ir_rate_2: currentTaxPolicy.value ? Number(currentTaxPolicy.value.ir_brackets?.[1]?.rate ?? 0) * 100 : 15,
  ir_rate_3: currentTaxPolicy.value ? Number(currentTaxPolicy.value.ir_brackets?.[2]?.rate ?? 0) * 100 : 20,
  ir_rate_4: currentTaxPolicy.value ? Number(currentTaxPolicy.value.ir_brackets?.[3]?.rate ?? 0) * 100 : 25,
  ir_rate_5: currentTaxPolicy.value ? Number(currentTaxPolicy.value.ir_brackets?.[4]?.rate ?? 0) * 100 : 30,
  notes: '',
})

const openTaxPolicyCreate = () => { taxPolicyForm.clearErrors(); showTaxPolicyCreate.value = true }
const submitTaxPolicyCreate = () => {
  taxPolicyForm.post('/tax-policies', {
    preserveScroll: true,
    onSuccess: () => { showTaxPolicyCreate.value = false; success.value = 'Nueva política de impuestos creada.' },
  })
}

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

const vacationStatusMeta = (value) => ({
  pending: { label: 'Pendiente', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  approved: { label: 'Aprobada', class: 'bg-[#E9F8EE] text-[#187A31]' },
  rejected: { label: 'Rechazada', class: 'bg-[#FFE5E5] text-[#D70015]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })

const bonusStatusMeta = (value) => ({
  pending: { label: 'Pendiente', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  applied: { label: 'Aplicado', class: 'bg-[#E9F8EE] text-[#187A31]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })

const loanStatusMeta = (value) => ({
  active: { label: 'Activo', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  paid: { label: 'Pagado', class: 'bg-[#E9F8EE] text-[#187A31]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })

const leaveStatusMeta = (value) => ({
  pending: { label: 'Pendiente', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  approved: { label: 'Aprobado', class: 'bg-[#E9F8EE] text-[#187A31]' },
  rejected: { label: 'Rechazado', class: 'bg-[#FFE5E5] text-[#D70015]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })
const leaveTypeLabel = (value) => leaveTypeLabels[value] ?? value

const aguinaldoStatusMeta = (value) => ({
  draft: { label: 'Borrador', class: 'bg-[#F2F2F7] text-[#6E6E73]' },
  approved: { label: 'Aprobado', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  paid: { label: 'Pagado', class: 'bg-[#E9F8EE] text-[#187A31]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })

const settlementStatusMeta = (value) => ({
  draft: { label: 'Borrador', class: 'bg-[#F2F2F7] text-[#6E6E73]' },
  approved: { label: 'Aprobada', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  paid: { label: 'Pagada', class: 'bg-[#E9F8EE] text-[#187A31]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })

const deductionStatusMeta = (value) => ({
  pending: { label: 'Pendiente', class: 'bg-[#FFF6E5] text-[#B8860B]' },
  applied: { label: 'Aplicada', class: 'bg-[#E9F8EE] text-[#187A31]' },
}[value] ?? { label: value, class: 'bg-[#F2F2F7] text-[#6E6E73]' })

const percent = (value) => `${(Number(value) * 100).toFixed(2).replace(/\.?0+$/, '')}%`
const irBracketsSummary = (policy) => (policy.ir_brackets ?? [])
  .map((b) => `${b.threshold === null ? '+' : money(b.threshold)}: ${percent(b.rate)}`)
  .join(' · ')

const money = (value) => new Intl.NumberFormat('es-NI', { style: 'currency', currency: 'NIO' }).format(value ?? 0)
const dateLabel = (value) => value
  ? new Intl.DateTimeFormat('es-NI', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
  : '—'

const matchesFilters = (record) => {
  if (employeeFilter.value !== 'all' && String(record.employee_id) !== employeeFilter.value) return false
  if (statusFilter.value !== 'all' && record.status !== statusFilter.value) return false
  const term = search.value.trim().toLocaleLowerCase()
  if (!term) return true
  return (record.employee?.full_name ?? '').toLocaleLowerCase().includes(term)
}

const filteredVacations = computed(() => props.vacations.filter(matchesFilters))
const filteredBonuses = computed(() => props.bonuses.filter(matchesFilters))
const filteredDeductions = computed(() => props.deductions.filter(matchesFilters))
const filteredLoans = computed(() => props.loans.filter(matchesFilters))
const filteredLeaves = computed(() => props.leaves.filter(matchesFilters))

const openGenerate = () => {
  form.reset()
  form.clearErrors()
  showGenerate.value = true
}
const closeGenerate = () => {
  if (!form.processing) {
    showGenerate.value = false
  }
}
const submitGenerate = () => {
  form.post('/payroll', {
    preserveScroll: true,
    onSuccess: () => {
      showGenerate.value = false
      form.reset()
      success.value = 'Planilla generada exitosamente.'
    },
  })
}
const confirmDuplicateAndGenerate = () => {
  form.confirm_duplicate = true
  submitGenerate()
}
const onGenerateDatesChanged = () => {
  // Si cambian las fechas después de ver la advertencia, hay que volver a
  // evaluarlas desde cero — no arrastrar una confirmación para otro rango.
  form.confirm_duplicate = false
}

const openGenerateAguinaldo = () => {
  aguinaldoForm.reset()
  aguinaldoForm.clearErrors()
  aguinaldoForm.year = props.currentYear
  showGenerateAguinaldo.value = true
}
const closeGenerateAguinaldo = () => {
  if (!aguinaldoForm.processing) {
    showGenerateAguinaldo.value = false
  }
}
const submitGenerateAguinaldo = () => {
  aguinaldoForm.post('/aguinaldo', {
    preserveScroll: true,
    onSuccess: () => {
      showGenerateAguinaldo.value = false
      aguinaldoForm.reset()
      success.value = 'Aguinaldo generado exitosamente.'
    },
  })
}
</script>

<template>
  <AppShell>
    <Head title="Nómina" />
    <div class="mx-auto max-w-7xl">
      <header class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div class="mb-2 flex items-center gap-2 text-xs font-semibold text-[#007AFF]">
            <Calculator class="size-4" /> Recursos humanos
          </div>
          <h1 class="font-display text-2xl font-bold text-[#1D1D1F]">Nómina</h1>
        </div>
        <button v-if="tab === 'planillas'" type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#0066D6]" @click="openGenerate">
          <Plus class="size-4" /> Generar planilla
        </button>
        <button v-if="tab === 'aguinaldo'" type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#0066D6]" @click="openGenerateAguinaldo">
          <Plus class="size-4" /> Generar aguinaldo
        </button>
        <button v-if="tab === 'settlements'" type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#D70015] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#B8000F]" @click="openSettlementCreate">
          <UserMinus class="size-4" /> Liquidar colaborador
        </button>
        <button v-if="tab === 'taxes'" type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#0066D6]" @click="openTaxPolicyCreate">
          <Plus class="size-4" /> Nueva política
        </button>
      </header>

      <div v-if="success" class="mb-4 flex items-center justify-between rounded-xl bg-[#E9F8EE] px-4 py-3 text-sm text-[#187A31]">
        <span class="flex items-center gap-2"><Check class="size-4" />{{ success }}</span>
        <button type="button" @click="success = ''"><X class="size-3.5" /></button>
      </div>

      <div class="mb-5 rounded-2xl border border-[#E5E5E5] bg-white p-4 shadow-sm">
        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Al día</p>
        <div v-if="alerts.length" class="flex flex-wrap gap-2">
          <button
            v-for="(alert, index) in alerts" :key="index" type="button"
            :class="['inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition-colors', {
              'bg-[#FFE5E5] text-[#D70015] hover:bg-[#FFD1D1]': alert.tone === 'danger',
              'bg-[#FFF6E5] text-[#B8860B] hover:bg-[#FCEFCB]': alert.tone === 'warn',
              'bg-[#E8F2FF] text-[#007AFF] hover:bg-[#D6E9FF]': alert.tone === 'info',
            }]"
            @click="switchTab(alert.tab)"
          >
            <AlertTriangle class="size-3.5" /> {{ alert.label }}
          </button>
        </div>
        <p v-else class="flex items-center gap-1.5 text-sm font-medium text-[#34C759]"><CheckCircle2 class="size-4" /> Todo al día: sin planillas, solicitudes ni liquidaciones pendientes.</p>
      </div>

      <div v-if="tab === 'planillas'" class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div v-for="item in [
          { label: 'Borrador', value: stats.draft, color: 'text-[#6E6E73]' },
          { label: 'Aprobadas', value: stats.approved, color: 'text-[#B8860B]' },
          { label: 'Pagadas', value: stats.paid, color: 'text-[#34C759]' },
          { label: 'Colaboradores elegibles', value: stats.eligible_employees, color: 'text-[#007AFF]' },
        ]" :key="item.label" class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">{{ item.label }}</p>
          <p :class="['mt-1 text-xl font-bold', item.color]">{{ item.value }}</p>
        </div>
      </div>

      <div v-else-if="tab === 'aguinaldo'" class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-3">
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Años generados</p>
          <p class="mt-1 text-xl font-bold text-[#007AFF]">{{ stats.aguinaldo_years_generated }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Total acumulado</p>
          <p class="mt-1 text-xl font-bold text-[#34C759]">{{ money(stats.aguinaldo_total) }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Colaboradores elegibles</p>
          <p class="mt-1 text-xl font-bold text-[#AF52DE]">{{ stats.eligible_employees }}</p>
        </div>
      </div>

      <div v-else-if="tab === 'settlements'" class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Borrador</p>
          <p class="mt-1 text-xl font-bold text-[#6E6E73]">{{ stats.settlements_draft }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Aprobadas</p>
          <p class="mt-1 text-xl font-bold text-[#B8860B]">{{ stats.settlements_approved }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Pagadas</p>
          <p class="mt-1 text-xl font-bold text-[#34C759]">{{ stats.settlements_paid }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Total pagado</p>
          <p class="mt-1 text-xl font-bold text-[#1D1D1F]">{{ money(stats.settlements_paid_total) }}</p>
        </div>
      </div>

      <div v-else-if="!['taxes', 'settlements'].includes(tab)" class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-6">
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Vacaciones pendientes</p>
          <p class="mt-1 text-xl font-bold text-[#B8860B]">{{ stats.pending_vacations }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Permisos pendientes</p>
          <p class="mt-1 text-xl font-bold text-[#007AFF]">{{ stats.pending_leaves }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Bonos pendientes</p>
          <p class="mt-1 text-xl font-bold text-[#34C759]">{{ stats.pending_bonuses }}</p>
          <p class="text-xs text-[#8E8E93]">{{ money(stats.pending_bonus_total) }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Deducciones pendientes</p>
          <p class="mt-1 text-xl font-bold text-[#FF9500]">{{ stats.pending_deductions }}</p>
          <p class="text-xs text-[#8E8E93]">{{ money(stats.pending_deduction_total) }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Préstamos activos</p>
          <p class="mt-1 text-xl font-bold text-[#D70015]">{{ stats.active_loans }}</p>
        </div>
        <div class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Saldo en préstamos</p>
          <p class="mt-1 text-xl font-bold text-[#1D1D1F]">{{ money(stats.active_loan_balance) }}</p>
        </div>
      </div>

      <div v-else class="mb-4 rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">Política vigente</p>
        <p v-if="activeTaxPolicy" class="mt-1 text-sm font-semibold text-[#1D1D1F]">{{ activeTaxPolicy.name }} — INSS laboral {{ percent(activeTaxPolicy.inss_employee_rate) }}, INSS patronal {{ percent(activeTaxPolicy.inss_employer_rate) }}, INATEC {{ percent(activeTaxPolicy.inatec_rate) }}</p>
        <p v-else class="mt-1 text-sm text-[#D70015]">No hay ninguna política de impuestos vigente todavía.</p>
      </div>

      <section class="overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-1 border-b border-[#E5E5E5] p-3">
          <button
            v-for="t in tabs" :key="t.key" type="button"
            :class="['inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold transition-colors', tab === t.key ? 'bg-[#007AFF] text-white' : 'text-[#6E6E73] hover:bg-[#F5F5F7]']"
            @click="switchTab(t.key)"
          >
            <component :is="t.icon" class="size-4" /> {{ t.label }}
          </button>
        </div>

        <div v-if="exportableSections.includes(tab)" class="flex flex-wrap items-center gap-2 border-b border-[#E5E5E5] bg-[#FAFAFB] px-4 py-3">
          <span class="text-xs font-semibold text-[#6E6E73]">Exportar PDF</span>
          <select v-model="exportRange" class="h-9 rounded-lg border-0 bg-white px-2.5 text-xs font-medium text-[#6E6E73] shadow-sm focus:ring-2 focus:ring-[#007AFF]/20">
            <option value="day">De un día</option>
            <option value="week">De una semana</option>
            <option value="month">De un mes</option>
            <option value="year">De los últimos 12 meses</option>
            <option value="custom">Rango personalizado</option>
          </select>
          <input v-if="exportRange !== 'custom'" v-model="exportDate" type="date" class="h-9 rounded-lg border-0 bg-white px-2.5 text-xs shadow-sm focus:ring-2 focus:ring-[#007AFF]/20">
          <template v-else>
            <input v-model="exportStart" type="date" class="h-9 rounded-lg border-0 bg-white px-2.5 text-xs shadow-sm focus:ring-2 focus:ring-[#007AFF]/20">
            <span class="text-xs text-[#8E8E93]">a</span>
            <input v-model="exportEnd" type="date" class="h-9 rounded-lg border-0 bg-white px-2.5 text-xs shadow-sm focus:ring-2 focus:ring-[#007AFF]/20">
          </template>
          <select v-model="exportEmployeeId" class="h-9 rounded-lg border-0 bg-white px-2.5 text-xs font-medium text-[#6E6E73] shadow-sm focus:ring-2 focus:ring-[#007AFF]/20">
            <option value="">Reporte de todos</option>
            <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">Ticket de {{ employee.full_name }}</option>
          </select>
          <a
            :href="exportReady ? exportUrl : undefined" target="_blank"
            :class="['ml-auto inline-flex h-9 items-center gap-1.5 rounded-lg px-3 text-xs font-semibold text-white shadow-sm', exportReady ? 'bg-[#1D1D1F] hover:bg-black' : 'cursor-not-allowed bg-[#C7C7CC]']"
            @click="!exportReady && $event.preventDefault()"
          >
            <Printer class="size-3.5" /> {{ exportEmployeeId ? 'Imprimir ticket' : 'Imprimir PDF' }}
          </a>
        </div>

        <!-- Planillas -->
        <template v-if="tab === 'planillas'">
          <div v-if="periods.data.length" class="divide-y divide-[#EFEFF1]">
            <Link v-for="period in periods.data" :key="period.id" :href="`/payroll/${period.id}`" class="grid gap-3 p-4 hover:bg-[#F8FAFF] md:grid-cols-[minmax(10rem,1fr)_1fr_1fr_auto_auto] md:items-center">
              <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#007AFF]/10 text-[#007AFF]"><Wallet class="size-5" /></div>
                <div><p class="text-sm font-semibold">{{ period.code }}</p><p class="text-xs text-[#8E8E93]">{{ frequencyLabel(period.pay_frequency) }}</p></div>
              </div>
              <div class="flex items-center gap-1.5 text-xs text-[#6E6E73]"><CalendarRange class="size-3.5" />{{ dateLabel(period.period_start) }} – {{ dateLabel(period.period_end) }}</div>
              <div><p class="text-sm font-semibold">{{ money(period.items_sum_net_pay) }}</p><p class="text-xs text-[#8E8E93]">{{ period.items_count }} colaborador(es)</p></div>
              <span :class="['w-fit rounded-full px-2.5 py-1 text-xs font-semibold', statusMeta(period.status).class]">{{ statusMeta(period.status).label }}</span>
              <ChevronRight class="size-4 text-[#C7C7CC] md:justify-self-end" />
            </Link>
          </div>
          <div v-else class="flex flex-col items-center py-14 text-center">
            <Clock class="size-10 text-[#C7C7CC]" /><p class="mt-3 text-sm font-semibold">No hay planillas generadas</p>
            <p class="mt-1 text-xs text-[#8E8E93]">Genera la primera planilla para el personal con sueldo asignado.</p>
            <button type="button" class="mt-3 text-xs font-semibold text-[#007AFF]" @click="openGenerate">Generar planilla</button>
          </div>
          <div v-if="periods.links?.length > 3" class="flex items-center justify-between border-t px-4 py-3">
            <p class="text-xs text-[#8E8E93]">{{ periods.from }}–{{ periods.to }} de {{ periods.total }}</p>
            <div class="flex gap-1"><Link v-for="link in periods.links.filter((item) => item.url)" :key="link.label" :href="link.url" :class="['rounded-lg px-2.5 py-1.5 text-xs', link.active ? 'bg-[#007AFF] text-white' : 'text-[#6E6E73]']" v-html="link.label" /></div>
          </div>
        </template>

        <!-- Aguinaldo -->
        <template v-else-if="tab === 'aguinaldo'">
          <div v-if="aguinaldos.length" class="divide-y divide-[#EFEFF1]">
            <Link v-for="aguinaldo in aguinaldos" :key="aguinaldo.id" :href="`/aguinaldo/${aguinaldo.id}`" class="grid gap-3 p-4 hover:bg-[#F8FAFF] md:grid-cols-[minmax(10rem,1fr)_1fr_1fr_auto_auto] md:items-center">
              <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#007AFF]/10 text-[#007AFF]"><Gift class="size-5" /></div>
                <div><p class="text-sm font-semibold">{{ aguinaldo.code }}</p><p class="text-xs text-[#8E8E93]">Año {{ aguinaldo.year }}</p></div>
              </div>
              <div class="flex items-center gap-1.5 text-xs text-[#6E6E73]"><CalendarRange class="size-3.5" />{{ dateLabel(aguinaldo.period_start) }} – {{ dateLabel(aguinaldo.period_end) }}</div>
              <div><p class="text-sm font-semibold">{{ money(aguinaldo.items_sum_amount) }}</p><p class="text-xs text-[#8E8E93]">{{ aguinaldo.items_count }} colaborador(es)</p></div>
              <span :class="['w-fit rounded-full px-2.5 py-1 text-xs font-semibold', aguinaldoStatusMeta(aguinaldo.status).class]">{{ aguinaldoStatusMeta(aguinaldo.status).label }}</span>
              <ChevronRight class="size-4 text-[#C7C7CC] md:justify-self-end" />
            </Link>
          </div>
          <div v-else class="flex flex-col items-center py-14 text-center">
            <Gift class="size-10 text-[#C7C7CC]" /><p class="mt-3 text-sm font-semibold">No hay aguinaldos generados</p>
            <p class="mt-1 text-xs text-[#8E8E93]">Genera el décimo tercer mes para el año correspondiente.</p>
            <button type="button" class="mt-3 text-xs font-semibold text-[#007AFF]" @click="openGenerateAguinaldo">Generar aguinaldo</button>
          </div>
        </template>

        <!-- Liquidaciones -->
        <template v-else-if="tab === 'settlements'">
          <div v-if="settlements.length" class="divide-y divide-[#EFEFF1]">
            <Link v-for="settlement in settlements" :key="settlement.id" :href="`/settlements/${settlement.id}`" class="grid gap-3 p-4 hover:bg-[#F8FAFF] md:grid-cols-[minmax(10rem,1fr)_1fr_1fr_auto_auto] md:items-center">
              <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#D70015]/10 text-[#D70015]"><UserMinus class="size-5" /></div>
                <div><p class="text-sm font-semibold">{{ settlement.employee?.full_name }}</p><p class="text-xs text-[#8E8E93]">{{ settlement.code }} · {{ terminationTypeLabel(settlement.termination_type) }}</p></div>
              </div>
              <div class="flex items-center gap-1.5 text-xs text-[#6E6E73]"><CalendarRange class="size-3.5" />Salida: {{ dateLabel(settlement.termination_date) }}</div>
              <div><p class="text-sm font-semibold">{{ money(settlement.net_amount) }}</p><p class="text-xs text-[#8E8E93]">{{ settlement.employee?.role?.name }}</p></div>
              <span :class="['w-fit rounded-full px-2.5 py-1 text-xs font-semibold', settlementStatusMeta(settlement.status).class]">{{ settlementStatusMeta(settlement.status).label }}</span>
              <ChevronRight class="size-4 text-[#C7C7CC] md:justify-self-end" />
            </Link>
          </div>
          <div v-else class="flex flex-col items-center py-14 text-center">
            <UserMinus class="size-10 text-[#C7C7CC]" /><p class="mt-3 text-sm font-semibold">No hay liquidaciones registradas</p>
            <p class="mt-1 text-xs text-[#8E8E93]">Cuando un colaborador termine su relación laboral, liquídalo desde aquí.</p>
            <button type="button" class="mt-3 text-xs font-semibold text-[#D70015]" @click="openSettlementCreate">Liquidar colaborador</button>
          </div>
        </template>

        <!-- Filtros compartidos: Vacaciones / Permisos / Bonos / Deducciones / Préstamos -->
        <template v-else-if="!['taxes', 'settlements'].includes(tab)">
          <div class="flex flex-col gap-3 border-b border-[#E5E5E5] p-4 sm:flex-row sm:items-center">
            <div class="relative flex-1">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-[#8E8E93]" />
              <input v-model="search" type="search" placeholder="Buscar colaborador..." class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] pl-9 pr-3 text-sm focus:ring-2 focus:ring-[#007AFF]/20">
            </div>
            <select v-model="employeeFilter" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-xs font-semibold text-[#6E6E73] focus:ring-2 focus:ring-[#007AFF]/20">
              <option value="all">Todos los colaboradores</option>
              <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">{{ employee.full_name }}</option>
            </select>
            <select v-model="statusFilter" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-xs font-semibold text-[#6E6E73] focus:ring-2 focus:ring-[#007AFF]/20">
              <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <Link v-if="employeeFilter !== 'all'" :href="`/employees/${employeeFilter}/history`" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl border border-[#E5E5E5] px-3 text-xs font-semibold text-[#6E6E73] hover:bg-[#F5F5F7]" title="Ver e imprimir historial del colaborador"><Printer class="size-3.5" /> Historial</Link>
            <button v-if="tab === 'vacations'" type="button" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#AF52DE] px-3 text-xs font-semibold text-white shadow-sm hover:bg-[#9A3FC7]" @click="openVacationCreate"><Plus class="size-3.5" /> Nueva</button>
            <button v-if="tab === 'leaves'" type="button" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#007AFF] px-3 text-xs font-semibold text-white shadow-sm hover:bg-[#0066D6]" @click="openLeaveCreate"><Plus class="size-3.5" /> Nueva</button>
            <button v-if="tab === 'bonuses'" type="button" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#34C759] px-3 text-xs font-semibold text-white shadow-sm hover:bg-[#2AAE4C]" @click="openBonusCreate"><Plus class="size-3.5" /> Nuevo</button>
            <button v-if="tab === 'deductions'" type="button" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#FF9500] px-3 text-xs font-semibold text-white shadow-sm hover:bg-[#E08600]" @click="openDeductionCreate"><Plus class="size-3.5" /> Nueva</button>
            <button v-if="tab === 'loans'" type="button" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#D70015] px-3 text-xs font-semibold text-white shadow-sm hover:bg-[#B8000F]" @click="openLoanCreate"><Plus class="size-3.5" /> Nuevo</button>
          </div>

          <!-- Vacaciones -->
          <div v-if="tab === 'vacations'" class="divide-y divide-[#EFEFF1]">
            <div v-for="vacation in filteredVacations" :key="vacation.id" class="flex items-center justify-between gap-3 p-4 hover:bg-[#F8FAFF]">
              <Link :href="`/employees/${vacation.employee_id}`" class="flex min-w-0 items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#F8F2FC] text-[#AF52DE]"><UserRound class="size-4" /></div>
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium hover:text-[#007AFF]">{{ vacation.employee?.full_name }}</p>
                  <p class="truncate text-xs text-[#8E8E93]">{{ vacation.employee?.role?.name }} · {{ dateLabel(vacation.start_date) }} – {{ dateLabel(vacation.end_date) }} · {{ vacation.days }} día(s) · {{ vacation.paid ? 'con goce' : 'sin goce' }}</p>
                </div>
              </Link>
              <div class="flex items-center gap-2">
                <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', vacationStatusMeta(vacation.status).class]">{{ vacationStatusMeta(vacation.status).label }}</span>
                <button v-if="vacation.status !== 'rejected'" type="button" class="rounded-lg p-1.5 text-[#007AFF] hover:bg-[#E8F2FF]" title="Editar" @click="openVacationEdit(vacation)"><Pencil class="size-4" /></button>
                <template v-if="vacation.status === 'pending'">
                  <button type="button" class="rounded-lg p-1.5 text-[#187A31] hover:bg-[#E9F8EE]" title="Aprobar" @click="decideVacation(vacation, 'approved')"><CheckCircle2 class="size-4" /></button>
                  <button type="button" class="rounded-lg p-1.5 text-[#D70015] hover:bg-[#FFE5E5]" title="Rechazar" @click="decideVacation(vacation, 'rejected')"><XCircle class="size-4" /></button>
                  <button type="button" class="rounded-lg p-1.5 text-[#8E8E93] hover:bg-[#F5F5F7]" title="Eliminar" @click="deleteVacation(vacation)"><Trash2 class="size-4" /></button>
                </template>
              </div>
            </div>
            <p v-if="!filteredVacations.length" class="p-8 text-center text-sm text-[#8E8E93]">No hay solicitudes de vacaciones con estos filtros.</p>
          </div>

          <!-- Permisos -->
          <div v-if="tab === 'leaves'" class="divide-y divide-[#EFEFF1]">
            <div v-for="leave in filteredLeaves" :key="leave.id" class="flex items-center justify-between gap-3 p-4 hover:bg-[#F8FAFF]">
              <Link :href="`/employees/${leave.employee_id}`" class="flex min-w-0 items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#E8F2FF] text-[#007AFF]"><UserRound class="size-4" /></div>
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium hover:text-[#007AFF]">{{ leave.employee?.full_name }} <span class="font-normal text-[#8E8E93]">· {{ leaveTypeLabel(leave.type) }}</span></p>
                  <p class="truncate text-xs text-[#8E8E93]">{{ leave.employee?.role?.name }} · {{ dateLabel(leave.start_date) }} – {{ dateLabel(leave.end_date) }} · {{ leave.days }} día(s) · {{ leave.paid ? 'con goce' : 'sin goce' }}</p>
                </div>
              </Link>
              <div class="flex items-center gap-2">
                <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', leaveStatusMeta(leave.status).class]">{{ leaveStatusMeta(leave.status).label }}</span>
                <button v-if="leave.status !== 'rejected'" type="button" class="rounded-lg p-1.5 text-[#007AFF] hover:bg-[#E8F2FF]" title="Editar" @click="openLeaveEdit(leave)"><Pencil class="size-4" /></button>
                <template v-if="leave.status === 'pending'">
                  <button type="button" class="rounded-lg p-1.5 text-[#187A31] hover:bg-[#E9F8EE]" title="Aprobar" @click="decideLeave(leave, 'approved')"><CheckCircle2 class="size-4" /></button>
                  <button type="button" class="rounded-lg p-1.5 text-[#D70015] hover:bg-[#FFE5E5]" title="Rechazar" @click="decideLeave(leave, 'rejected')"><XCircle class="size-4" /></button>
                  <button type="button" class="rounded-lg p-1.5 text-[#8E8E93] hover:bg-[#F5F5F7]" title="Eliminar" @click="deleteLeave(leave)"><Trash2 class="size-4" /></button>
                </template>
              </div>
            </div>
            <p v-if="!filteredLeaves.length" class="p-8 text-center text-sm text-[#8E8E93]">No hay solicitudes de permisos con estos filtros.</p>
          </div>

          <!-- Bonos -->
          <div v-if="tab === 'bonuses'" class="divide-y divide-[#EFEFF1]">
            <div v-for="bonus in filteredBonuses" :key="bonus.id" class="flex items-center justify-between gap-3 p-4 hover:bg-[#F8FAFF]">
              <Link :href="`/employees/${bonus.employee_id}`" class="flex min-w-0 items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#E9F8EE] text-[#34C759]"><UserRound class="size-4" /></div>
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium hover:text-[#007AFF]">{{ bonus.employee?.full_name }}</p>
                  <p class="truncate text-xs text-[#8E8E93]">{{ bonus.employee?.role?.name }} · {{ bonus.concept }} · {{ dateLabel(bonus.bonus_date) }}</p>
                </div>
              </Link>
              <div class="flex items-center gap-2">
                <p class="text-sm font-semibold">{{ money(bonus.amount) }}</p>
                <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', bonusStatusMeta(bonus.status).class]">{{ bonusStatusMeta(bonus.status).label }}</span>
                <template v-if="bonus.status === 'pending'">
                  <button type="button" class="rounded-lg p-1.5 text-[#007AFF] hover:bg-[#E8F2FF]" title="Editar" @click="openBonusEdit(bonus)"><Pencil class="size-4" /></button>
                  <button type="button" class="rounded-lg p-1.5 text-[#8E8E93] hover:bg-[#F5F5F7]" title="Eliminar" @click="deleteBonus(bonus)"><Trash2 class="size-4" /></button>
                </template>
              </div>
            </div>
            <p v-if="!filteredBonuses.length" class="p-8 text-center text-sm text-[#8E8E93]">No hay bonos con estos filtros.</p>
          </div>

          <!-- Deducciones -->
          <div v-if="tab === 'deductions'" class="divide-y divide-[#EFEFF1]">
            <div v-for="deduction in filteredDeductions" :key="deduction.id" class="flex items-center justify-between gap-3 p-4 hover:bg-[#F8FAFF]">
              <Link :href="`/employees/${deduction.employee_id}`" class="flex min-w-0 items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#FFF3E5] text-[#FF9500]"><UserRound class="size-4" /></div>
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium hover:text-[#007AFF]">{{ deduction.employee?.full_name }}</p>
                  <p class="truncate text-xs text-[#8E8E93]">{{ deduction.employee?.role?.name }} · {{ deduction.concept }} · {{ dateLabel(deduction.deduction_date) }}</p>
                </div>
              </Link>
              <div class="flex items-center gap-2">
                <p class="text-sm font-semibold text-[#D70015]">-{{ money(deduction.amount) }}</p>
                <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', deductionStatusMeta(deduction.status).class]">{{ deductionStatusMeta(deduction.status).label }}</span>
                <template v-if="deduction.status === 'pending'">
                  <button type="button" class="rounded-lg p-1.5 text-[#007AFF] hover:bg-[#E8F2FF]" title="Editar" @click="openDeductionEdit(deduction)"><Pencil class="size-4" /></button>
                  <button type="button" class="rounded-lg p-1.5 text-[#8E8E93] hover:bg-[#F5F5F7]" title="Eliminar" @click="deleteDeduction(deduction)"><Trash2 class="size-4" /></button>
                </template>
              </div>
            </div>
            <p v-if="!filteredDeductions.length" class="p-8 text-center text-sm text-[#8E8E93]">No hay deducciones con estos filtros.</p>
          </div>

          <!-- Préstamos -->
          <div v-if="tab === 'loans'" class="divide-y divide-[#EFEFF1]">
            <div v-for="loan in filteredLoans" :key="loan.id" class="flex items-center justify-between gap-3 p-4 hover:bg-[#F8FAFF]">
              <Link :href="`/employees/${loan.employee_id}`" class="flex min-w-0 items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#FFE5E5] text-[#D70015]"><UserRound class="size-4" /></div>
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium hover:text-[#007AFF]">{{ loan.employee?.full_name }}</p>
                  <p class="truncate text-xs text-[#8E8E93]">{{ loan.employee?.role?.name }} · {{ money(loan.amount) }} · cuota {{ money(loan.installment_amount) }} · {{ dateLabel(loan.granted_at) }}</p>
                </div>
              </Link>
              <div class="flex items-center gap-2">
                <p class="text-sm font-semibold">{{ loan.status === 'paid' ? 'Pagado' : `Saldo ${money(loan.remaining_balance)}` }}</p>
                <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', loanStatusMeta(loan.status).class]">{{ loanStatusMeta(loan.status).label }}</span>
                <template v-if="Number(loan.remaining_balance) === Number(loan.amount)">
                  <button type="button" class="rounded-lg p-1.5 text-[#007AFF] hover:bg-[#E8F2FF]" title="Editar" @click="openLoanEdit(loan)"><Pencil class="size-4" /></button>
                  <button type="button" class="rounded-lg p-1.5 text-[#8E8E93] hover:bg-[#F5F5F7]" title="Eliminar" @click="deleteLoan(loan)"><Trash2 class="size-4" /></button>
                </template>
                <span v-else class="max-w-[11rem] text-right text-[11px] leading-tight text-[#8E8E93]" title="Ya se le descontaron cuotas por planilla; editarlo ahora rompería ese historial.">Ya tiene cuotas<br>descontadas</span>
              </div>
            </div>
            <p v-if="!filteredLoans.length" class="p-8 text-center text-sm text-[#8E8E93]">No hay préstamos con estos filtros.</p>
          </div>
        </template>

        <!-- Impuestos -->
        <template v-else>
          <div v-if="taxPolicies.length" class="divide-y divide-[#EFEFF1]">
            <div v-for="policy in taxPolicies" :key="policy.id" class="p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="flex items-center gap-2 text-sm font-semibold">
                    {{ policy.name }}
                    <span v-if="activeTaxPolicy?.id === policy.id" class="rounded-full bg-[#E9F8EE] px-2 py-0.5 text-[10px] font-semibold text-[#187A31]">Vigente</span>
                    <span v-else-if="policy.effective_from.slice(0, 10) > new Date().toISOString().slice(0, 10)" class="rounded-full bg-[#FFF6E5] px-2 py-0.5 text-[10px] font-semibold text-[#B8860B]">Futura</span>
                  </p>
                  <p class="text-xs text-[#8E8E93]">Desde {{ dateLabel(policy.effective_from) }} · INSS laboral {{ percent(policy.inss_employee_rate) }} · INSS patronal {{ percent(policy.inss_employer_rate) }} · INATEC {{ percent(policy.inatec_rate) }}</p>
                  <p class="mt-1 text-xs text-[#8E8E93]">IR: {{ irBracketsSummary(policy) }}</p>
                  <p v-if="policy.notes" class="mt-1 text-xs text-[#8E8E93]">{{ policy.notes }}</p>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="flex flex-col items-center py-14 text-center">
            <Percent class="size-10 text-[#C7C7CC]" /><p class="mt-3 text-sm font-semibold">No hay ninguna política de impuestos</p>
            <p class="mt-1 text-xs text-[#8E8E93]">Crea la primera con las tasas de INSS, INATEC e IR vigentes.</p>
            <button type="button" class="mt-3 text-xs font-semibold text-[#007AFF]" @click="openTaxPolicyCreate">Nueva política</button>
          </div>
        </template>
      </section>
    </div>

    <div v-if="showGenerate" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="closeGenerate">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitGenerate">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-[#E8F2FF] text-[#007AFF]"><Calculator class="size-5" /></div>
            <div><h2 class="text-lg font-bold">Generar planilla</h2><p class="text-xs text-[#8E8E93]">Calcula INSS e IR para el personal activo con esta frecuencia.</p></div>
          </div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="closeGenerate"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div>
            <label class="mb-1 block text-xs font-semibold">Frecuencia de pago</label>
            <select v-model="form.pay_frequency" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm focus:ring-2 focus:ring-[#007AFF]/20">
              <option value="weekly">Semanal</option>
              <option value="biweekly">Quincenal</option>
              <option value="monthly">Mensual</option>
            </select>
            <p v-if="form.errors.pay_frequency" class="mt-1 text-xs text-[#D70015]">{{ form.errors.pay_frequency }}</p>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Inicio del periodo</label><input v-model="form.period_start" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm" @input="onGenerateDatesChanged"><p v-if="form.errors.period_start" class="mt-1 text-xs text-[#D70015]">{{ form.errors.period_start }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Fin del periodo</label><input v-model="form.period_end" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm" @input="onGenerateDatesChanged"><p v-if="form.errors.period_end" class="mt-1 text-xs text-[#D70015]">{{ form.errors.period_end }}</p></div>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label>
            <textarea v-model="form.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" placeholder="Referencia interna" />
          </div>
          <div v-if="form.errors.period_overlap" class="space-y-2 rounded-xl bg-[#FFF6E5] p-3 text-xs text-[#8A6300]">
            <div class="flex items-start gap-2">
              <AlertTriangle class="mt-0.5 size-3.5 shrink-0" />
              <span>{{ form.errors.period_overlap }}</span>
            </div>
            <button type="button" class="h-8 w-full rounded-lg bg-[#B8860B] text-xs font-semibold text-white hover:bg-[#966E08]" @click="confirmDuplicateAndGenerate">Sí, generar de todos modos</button>
          </div>
          <div v-else class="flex items-start gap-2 rounded-xl bg-[#F1F6FF] p-3 text-xs text-[#245DA8]">
            <UsersRound class="mt-0.5 size-3.5 shrink-0" />
            <span>Se incluirán todos los colaboradores activos con sueldo asignado, sin importar su frecuencia individual. La frecuencia elegida aquí solo se usa para calcular el IR de esta planilla.</span>
          </div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4">
          <button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="closeGenerate">Cancelar</button>
          <button type="submit" :disabled="form.processing" class="h-10 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ form.processing ? 'Generando...' : 'Generar planilla' }}</button>
        </footer>
      </form>
    </div>

    <div v-if="showGenerateAguinaldo" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="closeGenerateAguinaldo">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitGenerateAguinaldo">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-[#E8F2FF] text-[#007AFF]"><Gift class="size-5" /></div>
            <div><h2 class="text-lg font-bold">Generar aguinaldo</h2><p class="text-xs text-[#8E8E93]">Décimo tercer mes, del 1 de diciembre al 30 de noviembre. Exento de INSS e IR.</p></div>
          </div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="closeGenerateAguinaldo"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div>
            <label class="mb-1 block text-xs font-semibold">Año</label>
            <input v-model.number="aguinaldoForm.year" type="number" :min="2020" :max="currentYear + 1" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
            <p v-if="aguinaldoForm.errors.year" class="mt-1 text-xs text-[#D70015]">{{ aguinaldoForm.errors.year }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label>
            <textarea v-model="aguinaldoForm.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" placeholder="Referencia interna" />
          </div>
          <div class="flex items-start gap-2 rounded-xl bg-[#F1F6FF] p-3 text-xs text-[#245DA8]">
            <UsersRound class="mt-0.5 size-3.5 shrink-0" />
            <span>Se calcula proporcional al tiempo laborado dentro del periodo. Quien trabajó el año completo recibe un mes de sueldo.</span>
          </div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4">
          <button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="closeGenerateAguinaldo">Cancelar</button>
          <button type="submit" :disabled="aguinaldoForm.processing" class="h-10 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ aguinaldoForm.processing ? 'Generando...' : 'Generar aguinaldo' }}</button>
        </footer>
      </form>
    </div>

    <!-- Modal: Nueva solicitud de vacaciones -->
    <div v-if="showVacationCreate" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showVacationCreate = false; editingVacation = null">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitVacationCreate">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#F8F2FC] text-[#AF52DE]"><CalendarDays class="size-5" /></div><div><h2 class="text-lg font-bold">{{ editingVacation ? 'Editar solicitud de vacaciones' : 'Nueva solicitud de vacaciones' }}</h2></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showVacationCreate = false; editingVacation = null"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div>
            <label class="mb-1 block text-xs font-semibold">Colaborador</label>
            <select v-model="vacationForm.employee_id" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              <option value="">Seleccionar</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.full_name }}</option>
            </select>
            <p v-if="vacationForm.errors.employee_id" class="mt-1 text-xs text-[#D70015]">{{ vacationForm.errors.employee_id }}</p>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Inicio</label><input v-model="vacationForm.start_date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="vacationForm.errors.start_date" class="mt-1 text-xs text-[#D70015]">{{ vacationForm.errors.start_date }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Fin</label><input v-model="vacationForm.end_date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="vacationForm.errors.end_date" class="mt-1 text-xs text-[#D70015]">{{ vacationForm.errors.end_date }}</p></div>
          </div>
          <div class="flex items-center justify-between rounded-xl bg-[#F5F5F7] p-3">
            <div><p class="text-sm font-semibold">Con goce de salario</p><p class="text-xs text-[#8E8E93]">Si está desactivado, esos días no se pagan en la planilla.</p></div>
            <button type="button" :class="['relative h-6 w-11 rounded-full', vacationForm.paid ? 'bg-[#34C759]' : 'bg-[#C7C7CC]']" @click="vacationForm.paid = !vacationForm.paid"><span :class="['absolute top-0.5 size-5 rounded-full bg-white shadow', vacationForm.paid ? 'left-5.5' : 'left-0.5']" /></button>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="vacationForm.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4"><button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showVacationCreate = false; editingVacation = null">Cancelar</button><button type="submit" :disabled="vacationForm.processing || !vacationForm.employee_id" class="h-10 rounded-xl bg-[#AF52DE] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ vacationForm.processing ? 'Guardando...' : (editingVacation ? 'Guardar cambios' : 'Solicitar') }}</button></footer>
      </form>
    </div>

    <!-- Modal: Nueva solicitud de permiso -->
    <div v-if="showLeaveCreate" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showLeaveCreate = false; editingLeave = null">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitLeaveCreate">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#E8F2FF] text-[#007AFF]"><FileText class="size-5" /></div><div><h2 class="text-lg font-bold">{{ editingLeave ? 'Editar permiso' : 'Nueva solicitud de permiso' }}</h2></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showLeaveCreate = false; editingLeave = null"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div>
            <label class="mb-1 block text-xs font-semibold">Colaborador</label>
            <select v-model="leaveForm.employee_id" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              <option value="">Seleccionar</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.full_name }}</option>
            </select>
            <p v-if="leaveForm.errors.employee_id" class="mt-1 text-xs text-[#D70015]">{{ leaveForm.errors.employee_id }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold">Tipo de permiso</label>
            <select v-model="leaveForm.type" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm" @change="onLeaveTypeChange">
              <option v-for="t in leaveTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
            <p v-if="leaveForm.errors.type" class="mt-1 text-xs text-[#D70015]">{{ leaveForm.errors.type }}</p>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Inicio</label><input v-model="leaveForm.start_date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="leaveForm.errors.start_date" class="mt-1 text-xs text-[#D70015]">{{ leaveForm.errors.start_date }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Fin</label><input v-model="leaveForm.end_date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="leaveForm.errors.end_date" class="mt-1 text-xs text-[#D70015]">{{ leaveForm.errors.end_date }}</p></div>
          </div>
          <div class="flex items-center justify-between rounded-xl bg-[#F5F5F7] p-3">
            <div><p class="text-sm font-semibold">Con goce de salario</p><p class="text-xs text-[#8E8E93]">Se sugiere según el tipo; puedes ajustarlo.</p></div>
            <button type="button" :class="['relative h-6 w-11 rounded-full', leaveForm.paid ? 'bg-[#34C759]' : 'bg-[#C7C7CC]']" @click="leaveForm.paid = !leaveForm.paid"><span :class="['absolute top-0.5 size-5 rounded-full bg-white shadow', leaveForm.paid ? 'left-5.5' : 'left-0.5']" /></button>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="leaveForm.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4"><button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showLeaveCreate = false; editingLeave = null">Cancelar</button><button type="submit" :disabled="leaveForm.processing || !leaveForm.employee_id" class="h-10 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ leaveForm.processing ? 'Guardando...' : (editingLeave ? 'Guardar cambios' : 'Solicitar') }}</button></footer>
      </form>
    </div>

    <!-- Modal: Nuevo bono -->
    <div v-if="showBonusCreate" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showBonusCreate = false; editingBonus = null">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitBonusCreate">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#E9F8EE] text-[#34C759]"><Banknote class="size-5" /></div><div><h2 class="text-lg font-bold">{{ editingBonus ? 'Editar bono' : 'Nuevo bono' }}</h2><p class="text-xs text-[#8E8E93]">Se sumará al bruto de la próxima planilla del colaborador.</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showBonusCreate = false; editingBonus = null"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div>
            <label class="mb-1 block text-xs font-semibold">Colaborador</label>
            <select v-model="bonusForm.employee_id" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              <option value="">Seleccionar</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.full_name }}</option>
            </select>
            <p v-if="bonusForm.errors.employee_id" class="mt-1 text-xs text-[#D70015]">{{ bonusForm.errors.employee_id }}</p>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Concepto</label><input v-model="bonusForm.concept" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm" placeholder="Ej. Bono de productividad"><p v-if="bonusForm.errors.concept" class="mt-1 text-xs text-[#D70015]">{{ bonusForm.errors.concept }}</p></div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Monto</label><input v-model="bonusForm.amount" type="number" step="0.01" min="0" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="bonusForm.errors.amount" class="mt-1 text-xs text-[#D70015]">{{ bonusForm.errors.amount }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Fecha</label><input v-model="bonusForm.bonus_date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"></div>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="bonusForm.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4"><button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showBonusCreate = false; editingBonus = null">Cancelar</button><button type="submit" :disabled="bonusForm.processing || !bonusForm.employee_id" class="h-10 rounded-xl bg-[#34C759] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ bonusForm.processing ? 'Guardando...' : (editingBonus ? 'Guardar cambios' : 'Registrar') }}</button></footer>
      </form>
    </div>

    <!-- Modal: Nueva deducción -->
    <div v-if="showDeductionCreate" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showDeductionCreate = false; editingDeduction = null">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitDeductionCreate">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#FFF3E5] text-[#FF9500]"><MinusCircle class="size-5" /></div><div><h2 class="text-lg font-bold">{{ editingDeduction ? 'Editar deducción' : 'Nueva deducción' }}</h2><p class="text-xs text-[#8E8E93]">Faltas, daños a bienes de la empresa u otro descuento disciplinario.</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showDeductionCreate = false; editingDeduction = null"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div>
            <label class="mb-1 block text-xs font-semibold">Colaborador</label>
            <select v-model="deductionForm.employee_id" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              <option value="">Seleccionar</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.full_name }}</option>
            </select>
            <p v-if="deductionForm.errors.employee_id" class="mt-1 text-xs text-[#D70015]">{{ deductionForm.errors.employee_id }}</p>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Concepto</label><input v-model="deductionForm.concept" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm" placeholder="Ej. Daño a herramienta de trabajo"><p v-if="deductionForm.errors.concept" class="mt-1 text-xs text-[#D70015]">{{ deductionForm.errors.concept }}</p></div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Monto</label><input v-model="deductionForm.amount" type="number" step="0.01" min="0" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="deductionForm.errors.amount" class="mt-1 text-xs text-[#D70015]">{{ deductionForm.errors.amount }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Fecha</label><input v-model="deductionForm.deduction_date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"></div>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="deductionForm.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4"><button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showDeductionCreate = false; editingDeduction = null">Cancelar</button><button type="submit" :disabled="deductionForm.processing || !deductionForm.employee_id" class="h-10 rounded-xl bg-[#FF9500] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ deductionForm.processing ? 'Guardando...' : (editingDeduction ? 'Guardar cambios' : 'Registrar') }}</button></footer>
      </form>
    </div>

    <!-- Modal: Nuevo préstamo -->
    <div v-if="showLoanCreate" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showLoanCreate = false; editingLoan = null">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitLoanCreate">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#FFE5E5] text-[#D70015]"><HandCoins class="size-5" /></div><div><h2 class="text-lg font-bold">{{ editingLoan ? 'Editar préstamo' : 'Nuevo préstamo' }}</h2><p class="text-xs text-[#8E8E93]">La cuota se descontará del neto en cada planilla.</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showLoanCreate = false; editingLoan = null"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div>
            <label class="mb-1 block text-xs font-semibold">Colaborador</label>
            <select v-model="loanForm.employee_id" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              <option value="">Seleccionar</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.full_name }}</option>
            </select>
            <p v-if="loanForm.errors.employee_id" class="mt-1 text-xs text-[#D70015]">{{ loanForm.errors.employee_id }}</p>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Monto total</label><input v-model="loanForm.amount" type="number" step="0.01" min="0" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="loanForm.errors.amount" class="mt-1 text-xs text-[#D70015]">{{ loanForm.errors.amount }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Cuota por planilla</label><input v-model="loanForm.installment_amount" type="number" step="0.01" min="0" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="loanForm.errors.installment_amount" class="mt-1 text-xs text-[#D70015]">{{ loanForm.errors.installment_amount }}</p></div>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Fecha de entrega</label><input v-model="loanForm.granted_at" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"></div>
          <div><label class="mb-1 block text-xs font-semibold">Motivo <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="loanForm.reason" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4"><button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showLoanCreate = false; editingLoan = null">Cancelar</button><button type="submit" :disabled="loanForm.processing || !loanForm.employee_id" class="h-10 rounded-xl bg-[#D70015] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ loanForm.processing ? 'Guardando...' : (editingLoan ? 'Guardar cambios' : 'Registrar') }}</button></footer>
      </form>
    </div>

    <!-- Modal: Nueva política de impuestos -->
    <div v-if="showTaxPolicyCreate" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="showTaxPolicyCreate = false">
      <form class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitTaxPolicyCreate">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#E8F2FF] text-[#007AFF]"><Percent class="size-5" /></div><div><h2 class="text-lg font-bold">Nueva política de impuestos</h2><p class="text-xs text-[#8E8E93]">Úsala cuando cambie la ley. Las planillas ya generadas conservan la tasa con la que se calcularon.</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showTaxPolicyCreate = false"><X class="size-4" /></button>
        </header>
        <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5">
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Nombre</label><input v-model="taxPolicyForm.name" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm" placeholder="Ej. Tasas 2027"><p v-if="taxPolicyForm.errors.name" class="mt-1 text-xs text-[#D70015]">{{ taxPolicyForm.errors.name }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Vigente desde</label><input v-model="taxPolicyForm.effective_from" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><p v-if="taxPolicyForm.errors.effective_from" class="mt-1 text-xs text-[#D70015]">{{ taxPolicyForm.errors.effective_from }}</p></div>
          </div>

          <div>
            <p class="mb-2 text-xs font-semibold">Tasas fijas (%)</p>
            <div class="grid gap-3 sm:grid-cols-3">
              <div><label class="mb-1 block text-xs text-[#8E8E93]">INSS laboral</label><input v-model="taxPolicyForm.inss_employee_rate" type="number" step="0.01" min="0" max="100" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"></div>
              <div><label class="mb-1 block text-xs text-[#8E8E93]">INSS patronal</label><input v-model="taxPolicyForm.inss_employer_rate" type="number" step="0.01" min="0" max="100" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"></div>
              <div><label class="mb-1 block text-xs text-[#8E8E93]">INATEC</label><input v-model="taxPolicyForm.inatec_rate" type="number" step="0.01" min="0" max="100" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"></div>
            </div>
          </div>

          <div>
            <p class="mb-2 text-xs font-semibold">Tabla progresiva de IR</p>
            <div class="space-y-2">
              <div class="grid grid-cols-[1fr_auto_5rem] items-center gap-2 text-xs text-[#8E8E93]">
                <span>Hasta</span><span></span><span>Tasa %</span>
              </div>
              <div class="grid grid-cols-[1fr_auto_5rem] items-center gap-2">
                <input v-model="taxPolicyForm.ir_threshold_1" type="number" step="0.01" min="0" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
                <span class="text-xs text-[#8E8E93]">→</span>
                <input v-model="taxPolicyForm.ir_rate_1" type="number" step="0.01" min="0" max="100" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              </div>
              <div class="grid grid-cols-[1fr_auto_5rem] items-center gap-2">
                <input v-model="taxPolicyForm.ir_threshold_2" type="number" step="0.01" min="0" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
                <span class="text-xs text-[#8E8E93]">→</span>
                <input v-model="taxPolicyForm.ir_rate_2" type="number" step="0.01" min="0" max="100" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              </div>
              <div class="grid grid-cols-[1fr_auto_5rem] items-center gap-2">
                <input v-model="taxPolicyForm.ir_threshold_3" type="number" step="0.01" min="0" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
                <span class="text-xs text-[#8E8E93]">→</span>
                <input v-model="taxPolicyForm.ir_rate_3" type="number" step="0.01" min="0" max="100" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              </div>
              <div class="grid grid-cols-[1fr_auto_5rem] items-center gap-2">
                <input v-model="taxPolicyForm.ir_threshold_4" type="number" step="0.01" min="0" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
                <span class="text-xs text-[#8E8E93]">→</span>
                <input v-model="taxPolicyForm.ir_rate_4" type="number" step="0.01" min="0" max="100" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              </div>
              <div class="grid grid-cols-[1fr_auto_5rem] items-center gap-2">
                <span class="flex h-10 items-center rounded-xl bg-[#F5F5F7] px-3 text-sm text-[#8E8E93]">En adelante</span>
                <span class="text-xs text-[#8E8E93]">→</span>
                <input v-model="taxPolicyForm.ir_rate_5" type="number" step="0.01" min="0" max="100" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              </div>
              <p v-if="taxPolicyForm.errors.ir_threshold_2 || taxPolicyForm.errors.ir_threshold_3 || taxPolicyForm.errors.ir_threshold_4" class="text-xs text-[#D70015]">{{ taxPolicyForm.errors.ir_threshold_2 || taxPolicyForm.errors.ir_threshold_3 || taxPolicyForm.errors.ir_threshold_4 }}</p>
            </div>
          </div>

          <div><label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="taxPolicyForm.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" placeholder="Ej. Reforma a la Ley de Concertación Tributaria, decreto..." /></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4">
          <button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showTaxPolicyCreate = false">Cancelar</button>
          <button type="submit" :disabled="taxPolicyForm.processing" class="h-10 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ taxPolicyForm.processing ? 'Guardando...' : 'Crear política' }}</button>
        </footer>
      </form>
    </div>

    <!-- Modal: Liquidar colaborador -->
    <div v-if="showSettlementCreate" class="fixed inset-0 z-[70] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="closeSettlementCreate">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitSettlementCreate">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#FFE5E5] text-[#D70015]"><UserMinus class="size-5" /></div><div><h2 class="text-lg font-bold">Liquidar colaborador</h2><p class="text-xs text-[#8E8E93]">Calcula salario pendiente, vacaciones, aguinaldo e indemnización proporcionales.</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="closeSettlementCreate"><X class="size-4" /></button>
        </header>
        <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5">
          <div>
            <label class="mb-1 block text-xs font-semibold">Colaborador</label>
            <select v-model="settlementForm.employee_id" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              <option value="" disabled>Seleccionar</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.full_name }}</option>
            </select>
            <p v-if="settlementForm.errors.employee_id" class="mt-1 text-xs text-[#D70015]">{{ settlementForm.errors.employee_id }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold">Tipo de terminación</label>
            <select v-model="settlementForm.termination_type" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              <option value="unjustified_dismissal">Despido sin causa</option>
              <option value="resignation">Renuncia voluntaria</option>
              <option value="justified_dismissal">Despido con causa justificada</option>
              <option value="mutual_agreement">Mutuo acuerdo</option>
            </select>
            <p v-if="settlementForm.errors.termination_type" class="mt-1 text-xs text-[#D70015]">{{ settlementForm.errors.termination_type }}</p>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold">Fecha de salida</label>
              <input v-model="settlementForm.termination_date" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm" @change="onSettlementTerminationDateChange">
              <p v-if="settlementForm.errors.termination_date" class="mt-1 text-xs text-[#D70015]">{{ settlementForm.errors.termination_date }}</p>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold">Salario pendiente desde</label>
              <input v-model="settlementForm.pending_salary_start" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
              <p v-if="settlementForm.errors.pending_salary_start" class="mt-1 text-xs text-[#D70015]">{{ settlementForm.errors.pending_salary_start }}</p>
            </div>
          </div>
          <div>
            <p class="mb-2 text-xs font-semibold">Indemnización por antigüedad</p>
            <div class="flex gap-2">
              <button type="button" :class="['flex-1 rounded-xl border px-3 py-2 text-xs font-semibold', settlementForm.severance_method === 'legal' ? 'border-[#D70015] bg-[#FFE5E5] text-[#D70015]' : 'border-[#E5E5E5] text-[#6E6E73]']" @click="settlementForm.severance_method = 'legal'">Ley de Nicaragua</button>
              <button type="button" :class="['flex-1 rounded-xl border px-3 py-2 text-xs font-semibold', settlementForm.severance_method === 'manual' ? 'border-[#D70015] bg-[#FFE5E5] text-[#D70015]' : 'border-[#E5E5E5] text-[#6E6E73]']" @click="settlementForm.severance_method = 'manual'">Monto manual</button>
            </div>
            <p class="mt-1.5 text-xs text-[#8E8E93]">Ley: 1 mes por año (hasta 3 años), luego 20 días por año adicional; tope de 5 meses.</p>
            <input v-if="settlementForm.severance_method === 'manual'" v-model="settlementForm.severance_amount" type="number" step="0.01" min="0" placeholder="Monto de indemnización" class="mt-2 h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm">
            <p v-if="settlementForm.errors.severance_amount" class="mt-1 text-xs text-[#D70015]">{{ settlementForm.errors.severance_amount }}</p>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Notas <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="settlementForm.notes" rows="2" maxlength="500" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
          <div class="flex items-start gap-2 rounded-xl bg-[#F1F6FF] p-3 text-xs text-[#245DA8]">
            <ShieldCheck class="mt-0.5 size-3.5 shrink-0" />
            <span>Vacaciones proporcionales, aguinaldo proporcional y descuento de préstamos/deducciones pendientes se calculan automáticamente. Podrás revisar el desglose completo antes de aprobar.</span>
          </div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4">
          <button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="closeSettlementCreate">Cancelar</button>
          <button type="submit" :disabled="settlementForm.processing || !settlementForm.employee_id" class="h-10 rounded-xl bg-[#D70015] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ settlementForm.processing ? 'Calculando...' : 'Calcular liquidación' }}</button>
        </footer>
      </form>
    </div>
  </AppShell>
</template>
