<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { CalendarDays, Check, Wallet, X } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  employeeId: { type: [Number, String], default: '' },
  employees: { type: Array, default: () => [] },
  deductionTypeOptions: { type: Array, default: () => [] },
  deductionStatusOptions: { type: Array, default: () => [] },
  lockEmployee: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'submit'])

const form = useForm({
  employee_id: '',
  type: 'adelanto_salario',
  amount: '',
  total_amount: '',
  installment_amount: '',
  installments_total: '',
  deduction_date: new Date().toISOString().slice(0, 10),
  reason: '',
  status: 'activa',
})

const defaultType = computed(() => props.deductionTypeOptions[0]?.value ?? 'adelanto_salario')

const supportsInstallments = computed(() => ['adelanto_salario', 'prestamo'].includes(form.type))

const resetForm = () => {
  form.reset()
  form.employee_id = props.employeeId ? String(props.employeeId) : ''
  form.type = defaultType.value
  form.deduction_date = new Date().toISOString().slice(0, 10)
  form.status = 'activa'
  form.clearErrors()
}

watch(() => props.show, (visible) => {
  if (visible) resetForm()
})

const close = () => emit('close')
const submit = () => emit('submit', form)
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm" @click.self="close">
      <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="sticky top-0 flex items-start justify-between border-b border-[#E5E5E5] bg-white p-5">
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#007AFF]">Deducciones</p>
            <h2 class="font-display text-lg font-bold text-[#1D1D1F]">Agregar deducción</h2>
          </div>
          <button type="button" class="rounded-lg p-2 text-[#8E8E93] hover:bg-[#F5F5F7]" @click="close"><X class="size-5" /></button>
        </div>

        <form class="space-y-4 p-5" @submit.prevent="submit">
          <div v-if="!lockEmployee">
            <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Colaborador</label>
            <select v-model="form.employee_id" required class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50">
              <option value="">Seleccionar colaborador</option>
              <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">
                {{ employee.full_name }} — {{ employee.code }}
              </option>
            </select>
            <p v-if="form.errors.employee_id" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.employee_id }}</p>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Tipo de deducción</label>
              <select v-model="form.type" required class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50">
                <option v-for="option in deductionTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Estado</label>
              <select v-model="form.status" class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50">
                <option v-for="option in deductionStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">
                {{ form.type === 'adelanto_salario' ? 'Monto descontado en nómina (C$)' : supportsInstallments ? 'Monto de cuota (C$)' : 'Monto (C$)' }}
              </label>
              <input v-model="form.amount" type="number" min="0.01" step="0.01" required class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm tabular-nums focus:ring-2 focus:ring-[#007AFF]/50">
              <p v-if="form.errors.amount" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.amount }}</p>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Fecha</label>
              <input v-model="form.deduction_date" type="date" required class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50">
            </div>
          </div>

          <div v-if="supportsInstallments" class="grid gap-4 rounded-xl border border-[#E5E5E5] p-4 sm:grid-cols-3">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">
                {{ form.type === 'adelanto_salario' ? 'Adelanto total' : 'Monto total' }}
              </label>
              <input v-model="form.total_amount" type="number" min="0.01" step="0.01" class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm tabular-nums focus:ring-2 focus:ring-[#007AFF]/50">
              <p v-if="form.errors.total_amount" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.total_amount }}</p>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Cuota</label>
              <input v-model="form.installment_amount" type="number" min="0.01" step="0.01" class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm tabular-nums focus:ring-2 focus:ring-[#007AFF]/50">
              <p v-if="form.errors.installment_amount" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.installment_amount }}</p>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Número de cuotas</label>
              <input v-model="form.installments_total" type="number" min="1" step="1" class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm tabular-nums focus:ring-2 focus:ring-[#007AFF]/50">
              <p v-if="form.errors.installments_total" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.installments_total }}</p>
            </div>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Motivo / observación</label>
            <textarea v-model="form.reason" rows="3" class="w-full resize-none rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50" placeholder="Describe el motivo de la deducción..." />
          </div>

          <div class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
            <button type="button" class="rounded-xl border border-[#E5E5E5] px-4 py-2.5 text-sm font-medium hover:bg-[#F5F5F7]" @click="close">Cancelar</button>
            <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-xl bg-[#007AFF] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0056CC] disabled:opacity-50">
              <Check v-if="!form.processing" class="mr-2 size-4" />
              {{ form.processing ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
