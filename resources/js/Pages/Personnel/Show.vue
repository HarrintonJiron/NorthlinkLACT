<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppShell from '../../Components/AppShell.vue'
import EmployeeFormFields from '../../Components/EmployeeFormFields.vue'
import EmployeeAttendanceModal from '../../Components/EmployeeAttendanceModal.vue'
import EmployeeDeductionModal from '../../Components/EmployeeDeductionModal.vue'
import EmployeeDocumentModal from '../../Components/EmployeeDocumentModal.vue'
import {
  ArrowLeft,
  Check,
  FileText,
  MinusCircle,
  Pencil,
  Plus,
  Trash2,
  X,
} from '@lucide/vue'

const props = defineProps({
  employee: Object,
  attendances: Array,
  deductions: Array,
  documents: Array,
  roles: Array,
  plants: Array,
  employees: Array,
  statusOptions: Array,
  areaOptions: Array,
  contractTypeOptions: Array,
  paymentMethodOptions: Array,
  attendanceTypeOptions: Array,
  deductionTypeOptions: Array,
  deductionStatusOptions: Array,
  documentTypeOptions: Array,
})

const page = usePage()
const editing = ref(false)
const showAttendance = ref(false)
const showDeduction = ref(false)
const showDocument = ref(false)
const pendingDelete = ref(null)

const flashSuccess = computed(() => page.props.flash?.success)
const inssDeduction = computed(() => props.employee.inss_deduction ?? null)
const hasDeductions = computed(() => props.deductions.length > 0 || inssDeduction.value !== null)
const statusClass = computed(() => ({
  activo: 'bg-[#E9F8EE] text-[#187A31]',
  suspendido: 'bg-[#FFF4E5] text-[#FF9500]',
  retirado: 'bg-[#F2F2F7] text-[#6E6E73]',
}[props.employee.status] || 'bg-[#F2F2F7] text-[#6E6E73]'))

const initials = (name) => name.split(' ').slice(0, 2).map((part) => part[0]).join('').toUpperCase()
const money = (value) => value == null ? '—' : new Intl.NumberFormat('es-NI', { style: 'currency', currency: 'NIO' }).format(value)
const dateLabel = (value) => value ? new Intl.DateTimeFormat('es-NI', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value)) : '—'
const display = (value) => (value == null || value === '') ? '—' : value

const form = useForm({
  first_name: '',
  last_name: '',
  identity_number: '',
  employee_role_id: '',
  area: '',
  plant_id: '',
  hired_at: '',
  status: 'activo',
  contract_type: '',
  contract_start_date: '',
  contract_end_date: '',
  salary: '',
  inss_insured: false,
  inss_number: '',
  payment_method: '',
  bank_account: '',
  email: '',
  phone: '',
  address: '',
  emergency_contact_name: '',
  emergency_contact_phone: '',
})

const syncFormFromEmployee = () => {
  form.first_name = props.employee.first_name ?? ''
  form.last_name = props.employee.last_name ?? ''
  form.identity_number = props.employee.identity_number ?? ''
  form.employee_role_id = props.employee.employee_role_id ? String(props.employee.employee_role_id) : ''
  form.area = props.employee.area ?? ''
  form.plant_id = props.employee.plant_id ? String(props.employee.plant_id) : ''
  form.hired_at = props.employee.hired_at?.slice?.(0, 10) ?? props.employee.hired_at ?? ''
  form.status = props.employee.status ?? 'activo'
  form.contract_type = props.employee.contract_type ?? ''
  form.contract_start_date = props.employee.contract_start_date?.slice?.(0, 10) ?? props.employee.contract_start_date ?? ''
  form.contract_end_date = props.employee.contract_end_date?.slice?.(0, 10) ?? props.employee.contract_end_date ?? ''
  form.salary = props.employee.salary != null ? String(props.employee.salary) : ''
  form.inss_insured = props.employee.inss_insured ?? false
  form.inss_number = props.employee.inss_number ?? ''
  form.payment_method = props.employee.payment_method ?? ''
  form.bank_account = props.employee.bank_account ?? ''
  form.email = props.employee.email ?? ''
  form.phone = props.employee.phone ?? ''
  form.address = props.employee.address ?? ''
  form.emergency_contact_name = props.employee.emergency_contact_name ?? ''
  form.emergency_contact_phone = props.employee.emergency_contact_phone ?? ''
  form.clearErrors()
}

watch(() => props.employee.id, syncFormFromEmployee, { immediate: true })

const startEdit = () => {
  syncFormFromEmployee()
  editing.value = true
}

const cancelEdit = () => {
  syncFormFromEmployee()
  editing.value = false
}

const saveEmployee = () => {
  form.put(`/employees/${props.employee.id}`, {
    preserveScroll: true,
    onSuccess: () => { editing.value = false },
  })
}

const submitAttendance = (form) => {
  form.transform((data) => ({ ...data, employee_id: props.employee.id }))
    .post(`/employees/${props.employee.id}/attendances`, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => { showAttendance.value = false },
    })
}

const submitDeduction = (form) => {
  form.transform((data) => ({ ...data, employee_id: props.employee.id }))
    .post(`/employees/${props.employee.id}/deductions`, {
      preserveScroll: true,
      onSuccess: () => { showDeduction.value = false },
    })
}

const submitDocument = (form) => {
  form.post(`/employees/${props.employee.id}/documents`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => { showDocument.value = false },
  })
}

const confirmDelete = (item) => {
  pendingDelete.value = item
}

const executeDelete = () => {
  if (!pendingDelete.value) return
  router.delete(pendingDelete.value.url, {
    preserveScroll: true,
    onFinish: () => { pendingDelete.value = null },
  })
}

const attendanceTypeClass = (type) => ({
  presente: 'bg-[#E9F8EE] text-[#187A31]',
  ausente: 'bg-[#FFE5E5] text-[#D70015]',
  vacaciones: 'bg-[#E8F2FF] text-[#245DA8]',
  permiso: 'bg-[#FFF4E5] text-[#FF9500]',
}[type] || 'bg-[#F5F5F7] text-[#6E6E73]')

const groupTitleClass = 'text-[11px] font-semibold uppercase tracking-[0.14em] text-[#8E8E93]'
const fieldLabelClass = 'text-xs text-[#8E8E93]'
const fieldValueClass = 'mt-1 text-sm font-semibold text-[#1D1D1F]'
</script>

<template>
  <AppShell>
    <Head :title="`${employee.full_name} · Personal`" />

    <div class="mx-auto max-w-6xl">
      <Link href="/employees" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-[#007AFF] hover:text-[#0056CC]">
        <ArrowLeft class="size-4" /> Volver al listado
      </Link>

      <div v-if="flashSuccess" class="mb-4 flex items-center rounded-xl bg-[#E9F8EE] px-4 py-3 text-sm text-[#187A31]">
        <Check class="mr-2 size-4" />{{ flashSuccess }}
      </div>

      <!-- Panel único -->
      <article class="overflow-hidden rounded-[28px] border border-[#E5E5E5] bg-white shadow-sm">
        <!-- Encabezado -->
        <header class="border-b border-[#EFEFF1] bg-gradient-to-br from-[#F8FAFF] to-white px-6 py-6 sm:px-8">
          <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex items-start gap-4">
              <div class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-[#007AFF] text-lg font-bold text-white sm:size-[4.5rem]">
                {{ initials(employee.full_name) }}
              </div>
              <div>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                  <span class="rounded-lg bg-white px-2.5 py-1 text-xs font-semibold text-[#6E6E73] shadow-sm ring-1 ring-[#E5E5E5]">{{ employee.code }}</span>
                  <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', statusClass]">{{ employee.status_label }}</span>
                </div>
                <h1 class="font-display text-2xl font-bold text-[#1D1D1F] sm:text-3xl">{{ employee.full_name }}</h1>
                <p class="mt-1 text-sm text-[#6E6E73]">
                  {{ employee.role?.name || 'Sin cargo' }}
                  <span v-if="employee.area"> · {{ employee.area }}</span>
                  <span v-if="employee.plant?.name"> · {{ employee.plant.name }}</span>
                </p>
              </div>
            </div>
            <div class="flex flex-wrap gap-2">
              <template v-if="editing">
                <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-[#E5E5E5] bg-white px-4 py-2.5 text-sm font-semibold hover:bg-[#F5F5F7]" @click="cancelEdit">
                  <X class="size-4" /> Cancelar
                </button>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#007AFF] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0066D6]" :disabled="form.processing" @click="saveEmployee">
                  <Check class="size-4" /> {{ form.processing ? 'Guardando...' : 'Guardar cambios' }}
                </button>
              </template>
              <template v-else>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-[#E5E5E5] bg-white px-4 py-2.5 text-sm font-semibold hover:bg-[#F5F5F7]" @click="startEdit">
                  <Pencil class="size-4" /> Editar
                </button>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#007AFF] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0066D6]" @click="showAttendance = true">
                  <Plus class="size-4" /> Asistencia
                </button>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#1D1D1F] px-4 py-2.5 text-sm font-semibold text-white hover:bg-black" @click="showDeduction = true">
                  <MinusCircle class="size-4" /> Deducción
                </button>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-[#E5E5E5] bg-white px-4 py-2.5 text-sm font-semibold hover:bg-[#F5F5F7]" @click="showDocument = true">
                  <FileText class="size-4" /> Documento
                </button>
              </template>
            </div>
          </div>
        </header>

        <!-- Datos del colaborador -->
        <div class="space-y-8 px-6 py-8 sm:px-8">
          <form v-if="editing" @submit.prevent="saveEmployee">
            <div v-if="Object.keys(form.errors).length" class="mb-6 rounded-2xl bg-[#FFE5E5] px-4 py-3 text-sm text-[#D70015]">
              Revisa los campos marcados antes de guardar.
            </div>
            <EmployeeFormFields
              :form="form"
              :employee="employee"
              :roles="roles"
              :plants="plants"
              :status-options="statusOptions"
              :area-options="areaOptions"
              :contract-type-options="contractTypeOptions"
              :payment-method-options="paymentMethodOptions"
              :show-code="true"
            />
          </form>

          <template v-else>
          <section>
            <h2 :class="groupTitleClass">Identificación</h2>
            <dl class="mt-4 grid gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
              <div><dt :class="fieldLabelClass">Código</dt><dd :class="fieldValueClass">{{ employee.code }}</dd></div>
              <div><dt :class="fieldLabelClass">Cédula</dt><dd :class="fieldValueClass">{{ display(employee.identity_number) }}</dd></div>
              <div><dt :class="fieldLabelClass">Nombres</dt><dd :class="fieldValueClass">{{ employee.first_name }}</dd></div>
              <div><dt :class="fieldLabelClass">Apellidos</dt><dd :class="fieldValueClass">{{ employee.last_name }}</dd></div>
            </dl>
          </section>

          <section class="border-t border-[#EFEFF1] pt-8">
            <h2 :class="groupTitleClass">Información laboral</h2>
            <dl class="mt-4 grid gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
              <div><dt :class="fieldLabelClass">Cargo</dt><dd :class="fieldValueClass">{{ display(employee.role?.name) }}</dd></div>
              <div><dt :class="fieldLabelClass">Área</dt><dd :class="fieldValueClass">{{ display(employee.area) }}</dd></div>
              <div><dt :class="fieldLabelClass">Planta</dt><dd :class="fieldValueClass">{{ display(employee.plant?.name) }}</dd></div>
              <div><dt :class="fieldLabelClass">Fecha de ingreso</dt><dd :class="fieldValueClass">{{ dateLabel(employee.hired_at) }}</dd></div>
              <div><dt :class="fieldLabelClass">Estado</dt><dd class="mt-1"><span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', statusClass]">{{ employee.status_label }}</span></dd></div>
            </dl>
          </section>

          <section class="border-t border-[#EFEFF1] pt-8">
            <h2 :class="groupTitleClass">Contrato, salario y pago</h2>
            <dl class="mt-4 grid gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
              <div><dt :class="fieldLabelClass">Tipo de contrato</dt><dd :class="fieldValueClass">{{ display(employee.contract_type_label) }}</dd></div>
              <div><dt :class="fieldLabelClass">Inicio de contrato</dt><dd :class="fieldValueClass">{{ dateLabel(employee.contract_start_date) }}</dd></div>
              <div><dt :class="fieldLabelClass">Fin de contrato</dt><dd :class="fieldValueClass">{{ dateLabel(employee.contract_end_date) }}</dd></div>
              <div><dt :class="fieldLabelClass">Salario vigente</dt><dd :class="fieldValueClass">{{ money(employee.salary) }}</dd></div>
              <div><dt :class="fieldLabelClass">Forma de pago</dt><dd :class="fieldValueClass">{{ display(employee.payment_method_label) }}</dd></div>
              <div><dt :class="fieldLabelClass">Cuenta bancaria</dt><dd :class="fieldValueClass">{{ display(employee.bank_account) }}</dd></div>
              <div><dt :class="fieldLabelClass">Asegurado INSS</dt><dd :class="fieldValueClass">{{ employee.inss_insured ? 'Sí' : 'No' }}</dd></div>
              <div><dt :class="fieldLabelClass">Número INSS</dt><dd :class="fieldValueClass">{{ display(employee.inss_number) }}</dd></div>
              <div v-if="inssDeduction" class="sm:col-span-2 lg:col-span-4">
                <dt :class="fieldLabelClass">Deducción mensual INSS</dt>
                <dd :class="fieldValueClass">
                  {{ money(inssDeduction.amount) }}
                  <span class="ml-1 text-xs font-normal text-[#8E8E93]">({{ inssDeduction.rate_label }} sobre {{ money(inssDeduction.salary_base) }} — automático)</span>
                </dd>
              </div>
            </dl>
          </section>

          <section class="border-t border-[#EFEFF1] pt-8">
            <h2 :class="groupTitleClass">Contacto</h2>
            <dl class="mt-4 grid gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
              <div><dt :class="fieldLabelClass">Teléfono</dt><dd :class="fieldValueClass">{{ display(employee.phone) }}</dd></div>
              <div><dt :class="fieldLabelClass">Correo</dt><dd :class="fieldValueClass">{{ display(employee.email) }}</dd></div>
              <div class="sm:col-span-2 lg:col-span-3"><dt :class="fieldLabelClass">Dirección</dt><dd :class="fieldValueClass">{{ display(employee.address) }}</dd></div>
              <div><dt :class="fieldLabelClass">Contacto de emergencia</dt><dd :class="fieldValueClass">{{ display(employee.emergency_contact_name) }}</dd></div>
              <div><dt :class="fieldLabelClass">Teléfono de emergencia</dt><dd :class="fieldValueClass">{{ display(employee.emergency_contact_phone) }}</dd></div>
            </dl>
          </section>
          </template>

          <!-- Asistencias -->
          <section v-if="!editing" class="border-t border-[#EFEFF1] pt-8">
            <div class="mb-4 flex items-center justify-between gap-3">
              <h2 :class="groupTitleClass">Asistencias</h2>
              <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-[#007AFF]" @click="showAttendance = true">
                <Plus class="size-3.5" /> Registrar
              </button>
            </div>
            <div v-if="attendances.length" class="divide-y divide-[#EFEFF1] rounded-2xl border border-[#E5E5E5]">
              <article v-for="item in attendances" :key="item.id" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <div class="flex flex-wrap items-center gap-2">
                    <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', attendanceTypeClass(item.type)]">{{ item.type_label }}</span>
                    <span class="text-sm font-semibold">{{ dateLabel(item.attendance_date) }}</span>
                  </div>
                  <p class="mt-1 text-xs text-[#6E6E73]">
                    <span v-if="item.check_in">Entrada: {{ item.check_in }}</span>
                    <span v-if="item.check_out"> · Salida: {{ item.check_out }}</span>
                    <span v-if="item.notes"> · {{ item.notes }}</span>
                  </p>
                </div>
                <button type="button" class="self-start rounded-lg border border-[#E5E5E5] p-2 text-[#D70015] hover:bg-[#FFF5F5] sm:self-center" @click="confirmDelete({ label: 'esta asistencia', url: `/employees/${employee.id}/attendances/${item.id}` })">
                  <Trash2 class="size-4" />
                </button>
              </article>
            </div>
            <p v-else class="rounded-2xl bg-[#F5F5F7] py-8 text-center text-sm text-[#8E8E93]">No hay asistencias registradas.</p>
          </section>

          <!-- Deducciones -->
          <section v-if="!editing" class="border-t border-[#EFEFF1] pt-8">
            <div class="mb-4 flex items-center justify-between gap-3">
              <div>
                <h2 :class="groupTitleClass">Deducciones</h2>
                <p class="mt-1 text-xs text-[#8E8E93]">INSS automático (7%) · adelantos, préstamos y otras deducciones</p>
              </div>
              <button type="button" class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-[#007AFF]" @click="showDeduction = true">
                <Plus class="size-3.5" /> Agregar
              </button>
            </div>
            <div v-if="hasDeductions" class="overflow-hidden rounded-2xl border border-[#E5E5E5]">
              <article v-if="inssDeduction" class="border-b border-[#EFEFF1] bg-[#F8FAFF] p-4">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-full bg-[#E8F2FF] px-2.5 py-1 text-xs font-semibold text-[#245DA8]">{{ inssDeduction.type_label }}</span>
                  <span class="text-sm font-semibold">{{ money(inssDeduction.amount) }}</span>
                  <span class="rounded-full bg-[#E9F8EE] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#187A31]">Automático</span>
                </div>
                <p class="mt-1 text-xs text-[#6E6E73]">{{ inssDeduction.reason }} · {{ inssDeduction.rate_label }} sobre {{ money(inssDeduction.salary_base) }}</p>
              </article>
              <div v-if="deductions.length" class="divide-y divide-[#EFEFF1]">
                <article v-for="item in deductions" :key="item.id" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                  <div>
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="rounded-full bg-[#F5F5F7] px-2.5 py-1 text-xs font-semibold text-[#1D1D1F]">{{ item.type_label }}</span>
                      <span class="text-sm font-semibold">{{ money(item.amount) }}</span>
                      <span class="text-xs text-[#8E8E93]">{{ dateLabel(item.deduction_date) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-[#6E6E73]">
                      {{ item.reason || 'Sin observación' }}
                      <span v-if="item.installments_total"> · Cuotas: {{ item.installments_paid }}/{{ item.installments_total }}</span>
                    </p>
                  </div>
                  <button type="button" class="self-start rounded-lg border border-[#E5E5E5] p-2 text-[#D70015] hover:bg-[#FFF5F5] sm:self-center" @click="confirmDelete({ label: 'esta deducción', url: `/employees/${employee.id}/deductions/${item.id}` })">
                    <Trash2 class="size-4" />
                  </button>
                </article>
              </div>
            </div>
            <p v-else class="rounded-2xl bg-[#F5F5F7] py-8 text-center text-sm text-[#8E8E93]">No hay deducciones registradas.</p>
          </section>

          <!-- Documentos -->
          <section v-if="!editing" class="border-t border-[#EFEFF1] pt-8">
            <div class="mb-4 flex items-center justify-between gap-3">
              <h2 :class="groupTitleClass">Documentos del expediente</h2>
              <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-[#007AFF]" @click="showDocument = true">
                <Plus class="size-3.5" /> Cargar
              </button>
            </div>
            <div v-if="documents.length" class="divide-y divide-[#EFEFF1] rounded-2xl border border-[#E5E5E5]">
              <article v-for="item in documents" :key="item.id" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                  <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#F5F5F7] text-[#007AFF]">
                    <FileText class="size-5" />
                  </div>
                  <div>
                    <p class="text-sm font-semibold">{{ item.name }}</p>
                    <p class="text-xs text-[#8E8E93]">{{ item.type_label }} · {{ item.file_name }}</p>
                  </div>
                </div>
                <div class="flex gap-2 self-start sm:self-center">
                  <a :href="item.download_url" class="rounded-lg border border-[#E5E5E5] px-3 py-2 text-xs font-semibold text-[#007AFF] hover:bg-[#F8FAFF]">Descargar</a>
                  <button type="button" class="rounded-lg border border-[#E5E5E5] p-2 text-[#D70015] hover:bg-[#FFF5F5]" @click="confirmDelete({ label: 'este documento', url: `/employees/${employee.id}/documents/${item.id}` })">
                    <Trash2 class="size-4" />
                  </button>
                </div>
              </article>
            </div>
            <p v-else class="rounded-2xl bg-[#F5F5F7] py-8 text-center text-sm text-[#8E8E93]">No hay documentos cargados.</p>
          </section>
        </div>
      </article>
    </div>

    <EmployeeAttendanceModal
      :show="showAttendance"
      :employee-id="employee.id"
      :employees="employees"
      :attendance-type-options="attendanceTypeOptions"
      lock-employee
      @close="showAttendance = false"
      @submit="submitAttendance"
    />

    <EmployeeDeductionModal
      :show="showDeduction"
      :employee-id="employee.id"
      :employees="employees"
      :deduction-type-options="deductionTypeOptions"
      :deduction-status-options="deductionStatusOptions"
      lock-employee
      @close="showDeduction = false"
      @submit="submitDeduction"
    />

    <EmployeeDocumentModal
      :show="showDocument"
      :employee-id="employee.id"
      :document-type-options="documentTypeOptions"
      @close="showDocument = false"
      @submit="submitDocument"
    />

    <div v-if="pendingDelete" class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
      <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl">
        <h3 class="text-lg font-bold text-[#1D1D1F]">Confirmar eliminación</h3>
        <p class="mt-2 text-sm text-[#6E6E73]">¿Deseas eliminar {{ pendingDelete.label }}? Esta acción no se puede deshacer.</p>
        <div class="mt-5 flex justify-end gap-2">
          <button type="button" class="rounded-xl border border-[#E5E5E5] px-4 py-2.5 text-sm font-medium" @click="pendingDelete = null">Cancelar</button>
          <button type="button" class="rounded-xl bg-[#D70015] px-4 py-2.5 text-sm font-semibold text-white" @click="executeDelete">Eliminar</button>
        </div>
      </div>
    </div>
  </AppShell>
</template>
