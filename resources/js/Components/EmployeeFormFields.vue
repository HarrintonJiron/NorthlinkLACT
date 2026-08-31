<script setup>
import { computed } from 'vue'

const props = defineProps({
  form: { type: Object, required: true },
  employee: { type: Object, default: null },
  roles: { type: Array, default: () => [] },
  plants: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
  areaOptions: { type: Array, default: () => [] },
  contractTypeOptions: { type: Array, default: () => [] },
  paymentMethodOptions: { type: Array, default: () => [] },
  showCode: { type: Boolean, default: true },
})

const assignableRoles = computed(() => props.roles.filter((role) =>
  role.active || role.id === props.employee?.employee_role_id,
))

const showBankAccount = computed(() => props.form.payment_method === 'transferencia')
const showInssNumber = computed(() => props.form.inss_insured)
const showContractEnd = computed(() => ['temporal', 'por_obra', 'practicas'].includes(props.form.contract_type))

const inputClass = 'w-full rounded-xl border border-transparent bg-[#F5F5F7] px-3 py-2.5 text-sm text-[#1D1D1F] focus:border-[#007AFF]/30 focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50'
const labelClass = 'mb-1.5 block text-xs font-medium text-[#1D1D1F]'
const groupTitleClass = 'text-[11px] font-semibold uppercase tracking-[0.14em] text-[#8E8E93]'
</script>

<template>
  <div class="space-y-8">
    <div v-if="showCode && employee?.code" class="flex items-center gap-2 rounded-2xl bg-[#F5F5F7] px-4 py-3">
      <p class="text-[11px] text-[#8E8E93]">Código</p>
      <p class="font-semibold tabular-nums text-[#1D1D1F]">{{ employee.code }}</p>
    </div>
    <p v-else-if="showCode" class="rounded-2xl bg-[#F5F5F7] px-4 py-3 text-xs text-[#8E8E93]">
      El código se generará automáticamente al guardar.
    </p>

    <section>
      <h3 :class="groupTitleClass">Identificación</h3>
      <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
          <label :class="labelClass">Nombres</label>
          <input v-model="form.first_name" type="text" required :class="inputClass" autocomplete="given-name">
          <p v-if="form.errors.first_name" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.first_name }}</p>
        </div>
        <div>
          <label :class="labelClass">Apellidos</label>
          <input v-model="form.last_name" type="text" required :class="inputClass" autocomplete="family-name">
          <p v-if="form.errors.last_name" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.last_name }}</p>
        </div>
        <div class="sm:col-span-2 lg:col-span-1">
          <label :class="labelClass">Cédula</label>
          <input v-model="form.identity_number" type="text" :class="inputClass" autocomplete="off">
          <p v-if="form.errors.identity_number" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.identity_number }}</p>
        </div>
      </div>
    </section>

    <section class="border-t border-[#EFEFF1] pt-8">
      <h3 :class="groupTitleClass">Información laboral</h3>
      <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
          <label :class="labelClass">Cargo</label>
          <select v-model="form.employee_role_id" required :class="inputClass">
            <option value="">Seleccionar cargo</option>
            <option v-for="role in assignableRoles" :key="role.id" :value="String(role.id)">
              {{ role.name }}{{ role.active ? '' : ' (inactivo)' }}
            </option>
          </select>
          <p v-if="form.errors.employee_role_id" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.employee_role_id }}</p>
        </div>
        <div>
          <label :class="labelClass">Área</label>
          <select v-model="form.area" :class="inputClass">
            <option value="">Seleccionar área</option>
            <option v-for="area in areaOptions" :key="area.value" :value="area.value">{{ area.label }}</option>
          </select>
          <p v-if="form.errors.area" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.area }}</p>
        </div>
        <div>
          <label :class="labelClass">Planta</label>
          <select v-model="form.plant_id" :class="inputClass">
            <option value="">Sin planta asignada</option>
            <option v-for="plant in plants" :key="plant.id" :value="String(plant.id)">
              {{ plant.name }} ({{ plant.code }})
            </option>
          </select>
          <p v-if="form.errors.plant_id" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.plant_id }}</p>
        </div>
        <div>
          <label :class="labelClass">Fecha de ingreso</label>
          <input v-model="form.hired_at" type="date" :class="inputClass">
          <p v-if="form.errors.hired_at" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.hired_at }}</p>
        </div>
        <div>
          <label :class="labelClass">Estado</label>
          <select v-model="form.status" :class="inputClass">
            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
          <p v-if="form.errors.status" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.status }}</p>
        </div>
      </div>
    </section>

    <section class="border-t border-[#EFEFF1] pt-8">
      <h3 :class="groupTitleClass">Contrato</h3>
      <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
          <label :class="labelClass">Tipo de contrato</label>
          <select v-model="form.contract_type" :class="inputClass">
            <option value="">Seleccionar</option>
            <option v-for="option in contractTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
        </div>
        <div>
          <label :class="labelClass">Fecha de inicio</label>
          <input v-model="form.contract_start_date" type="date" :class="inputClass">
        </div>
        <div v-if="showContractEnd">
          <label :class="labelClass">Fecha de finalización</label>
          <input v-model="form.contract_end_date" type="date" :class="inputClass">
          <p v-if="form.errors.contract_end_date" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.contract_end_date }}</p>
        </div>
      </div>
    </section>

    <section class="border-t border-[#EFEFF1] pt-8">
      <h3 :class="groupTitleClass">Salario y seguro INSS</h3>
      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <div>
          <label :class="labelClass">Salario vigente (C$)</label>
          <input v-model="form.salary" type="number" min="0" step="0.01" :class="inputClass" inputmode="decimal">
          <p v-if="form.errors.salary" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.salary }}</p>
        </div>
        <div v-if="showInssNumber">
          <label :class="labelClass">Número INSS</label>
          <input v-model="form.inss_number" type="text" :class="inputClass">
        </div>
      </div>
      <label class="mt-4 flex cursor-pointer items-center justify-between rounded-2xl bg-[#F5F5F7] p-4">
        <div>
          <p class="text-sm font-semibold text-[#1D1D1F]">Asegurado INSS</p>
          <p class="text-xs text-[#8E8E93]">Se descontará automáticamente el 7% del salario bruto.</p>
        </div>
        <input v-model="form.inss_insured" type="checkbox" class="size-4 shrink-0 rounded border-[#C7C7CC] text-[#007AFF]">
      </label>
    </section>

    <section class="border-t border-[#EFEFF1] pt-8">
      <h3 :class="groupTitleClass">Forma de pago</h3>
      <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
          <label :class="labelClass">Método de pago</label>
          <select v-model="form.payment_method" :class="inputClass">
            <option value="">Seleccionar</option>
            <option v-for="option in paymentMethodOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
        </div>
        <div v-if="showBankAccount">
          <label :class="labelClass">Cuenta bancaria</label>
          <input v-model="form.bank_account" type="text" :class="inputClass">
        </div>
      </div>
    </section>

    <section class="border-t border-[#EFEFF1] pt-8">
      <h3 :class="groupTitleClass">Contacto</h3>
      <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
          <label :class="labelClass">Correo</label>
          <input v-model="form.email" type="email" :class="inputClass" autocomplete="email">
          <p v-if="form.errors.email" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.email }}</p>
        </div>
        <div>
          <label :class="labelClass">Teléfono</label>
          <input v-model="form.phone" type="tel" :class="inputClass" autocomplete="tel">
        </div>
        <div class="sm:col-span-2 lg:col-span-3">
          <label :class="labelClass">Dirección</label>
          <input v-model="form.address" type="text" :class="inputClass" autocomplete="street-address">
        </div>
      </div>
      <div class="mt-4 grid gap-4 rounded-2xl bg-[#F5F5F7] p-4 sm:grid-cols-2">
        <div>
          <label :class="labelClass">Contacto de emergencia</label>
          <input v-model="form.emergency_contact_name" type="text" :class="inputClass">
        </div>
        <div>
          <label :class="labelClass">Teléfono de emergencia</label>
          <input v-model="form.emergency_contact_phone" type="tel" :class="inputClass">
        </div>
      </div>
    </section>
  </div>
</template>
