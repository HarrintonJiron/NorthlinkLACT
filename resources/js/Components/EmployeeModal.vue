<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Check, X } from '@lucide/vue'
import EmployeeFormFields from './EmployeeFormFields.vue'

const props = defineProps({
  show: Boolean,
  employee: { type: Object, default: null },
  roles: { type: Array, default: () => [] },
  plants: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
  areaOptions: { type: Array, default: () => [] },
  contractTypeOptions: { type: Array, default: () => [] },
  paymentMethodOptions: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'submit'])

const isEditing = computed(() => Boolean(props.employee?.id))
const assignableRoles = computed(() => props.roles.filter((role) =>
  role.active || role.id === props.employee?.employee_role_id,
))

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

const errorMessages = computed(() => Object.values(form.errors).filter(Boolean))

const syncForm = () => {
  if (props.employee?.id) {
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
  } else {
    form.reset()
    form.status = 'activo'
    form.inss_insured = false
  }

  form.clearErrors()
}

watch(() => props.show, (visible) => {
  if (visible) syncForm()
})

const close = () => emit('close')
const submit = () => emit('submit', form)
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
      @click.self="close"
    >
      <div
        class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl"
        role="dialog"
        aria-modal="true"
        @click.stop
      >
        <div class="flex shrink-0 items-start justify-between border-b border-[#E5E5E5] px-6 py-5">
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#007AFF]">Personal</p>
            <h2 class="font-display text-xl font-bold text-[#1D1D1F]">
              {{ isEditing ? 'Editar colaborador' : 'Nuevo colaborador' }}
            </h2>
          </div>
          <button type="button" class="rounded-lg p-2 text-[#8E8E93] hover:bg-[#F5F5F7]" @click="close">
            <X class="size-5" />
          </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-6 py-5">
          <div v-if="errorMessages.length" class="mb-6 rounded-2xl bg-[#FFE5E5] px-4 py-3 text-sm text-[#D70015]">
            <p class="font-semibold">Revisa los campos marcados:</p>
            <ul class="mt-1 list-disc pl-4">
              <li v-for="message in errorMessages" :key="message">{{ message }}</li>
            </ul>
          </div>

          <p v-if="!assignableRoles.length" class="mb-6 rounded-2xl bg-[#FFF4E5] px-4 py-3 text-sm text-[#FF9500]">
            Crea al menos un cargo con <strong>Nuevo rol</strong> antes de registrar colaboradores.
          </p>

          <EmployeeFormFields
            :form="form"
            :employee="employee"
            :roles="roles"
            :plants="plants"
            :status-options="statusOptions"
            :area-options="areaOptions"
            :contract-type-options="contractTypeOptions"
            :payment-method-options="paymentMethodOptions"
          />
        </div>

        <div class="flex shrink-0 flex-col-reverse gap-2 border-t border-[#E5E5E5] bg-white px-6 py-4 sm:flex-row sm:justify-end">
          <button type="button" class="rounded-xl border border-[#E5E5E5] px-4 py-2.5 text-sm font-medium hover:bg-[#F5F5F7]" @click="close">Cancelar</button>
          <button
            type="button"
            :disabled="form.processing || !assignableRoles.length"
            class="inline-flex items-center justify-center rounded-xl bg-[#007AFF] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0056CC] disabled:cursor-not-allowed disabled:opacity-50"
            @click="submit"
          >
            <Check v-if="!form.processing" class="mr-2 size-4" />
            {{ form.processing ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
