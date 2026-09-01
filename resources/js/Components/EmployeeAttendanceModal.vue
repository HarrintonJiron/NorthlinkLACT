<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { CalendarDays, Check, Clock3, FileText, UserRound, X } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  employeeId: { type: [Number, String], default: '' },
  employees: { type: Array, default: () => [] },
  attendanceTypeOptions: { type: Array, default: () => [] },
  lockEmployee: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'submit'])

const form = useForm({
  employee_id: '',
  attendance_date: new Date().toISOString().slice(0, 10),
  type: 'presente',
  check_in: '',
  check_out: '',
  notes: '',
  justification: null,
})

const selectedType = computed(() => form.type)
const requiresCheckIn = computed(() => ['presente', 'entrada_tardia', 'salida_anticipada', 'dia_parcial'].includes(selectedType.value))
const requiresCheckOut = computed(() => ['presente', 'entrada_tardia', 'salida_anticipada', 'dia_parcial'].includes(selectedType.value))
const allowsJustification = computed(() => ['ausente', 'permiso', 'incapacidad', 'entrada_tardia', 'salida_anticipada', 'dia_parcial'].includes(selectedType.value))

const resetForm = () => {
  form.reset()
  form.employee_id = props.employeeId ? String(props.employeeId) : ''
  form.attendance_date = new Date().toISOString().slice(0, 10)
  form.type = 'presente'
  form.justification = null
  form.clearErrors()
}

watch(() => props.show, (visible) => {
  if (visible) resetForm()
})

const onFileChange = (event) => {
  form.justification = event.target.files?.[0] ?? null
}

const close = () => emit('close')
const submit = () => emit('submit', form)
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm" @click.self="close">
      <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="sticky top-0 flex items-start justify-between border-b border-[#E5E5E5] bg-white p-5">
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#007AFF]">Asistencias</p>
            <h2 class="font-display text-lg font-bold text-[#1D1D1F]">Registrar asistencia</h2>
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
              <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Fecha</label>
              <input v-model="form.attendance_date" type="date" required class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50">
              <p v-if="form.errors.attendance_date" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.attendance_date }}</p>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Tipo de asistencia</label>
              <select v-model="form.type" required class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50">
                <option v-for="option in attendanceTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
              <p v-if="form.errors.type" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.type }}</p>
            </div>
          </div>

          <div v-if="requiresCheckIn || requiresCheckOut" class="grid gap-4 sm:grid-cols-2">
            <div v-if="requiresCheckIn">
              <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Hora de entrada</label>
              <input v-model="form.check_in" type="time" class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50">
              <p v-if="form.errors.check_in" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.check_in }}</p>
            </div>
            <div v-if="requiresCheckOut">
              <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Hora de salida</label>
              <input v-model="form.check_out" type="time" class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50">
              <p v-if="form.errors.check_out" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.check_out }}</p>
            </div>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Observaciones</label>
            <textarea v-model="form.notes" rows="3" class="w-full resize-none rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50" placeholder="Detalle adicional..." />
          </div>

          <div v-if="allowsJustification">
            <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Justificación / documento</label>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full text-sm text-[#6E6E73]" @change="onFileChange">
            <p v-if="form.errors.justification" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.justification }}</p>
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
