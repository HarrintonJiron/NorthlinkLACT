<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Check, FileText, X } from '@lucide/vue'

const props = defineProps({
  show: Boolean,
  employeeId: { type: [Number, String], required: true },
  documentTypeOptions: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'submit'])

const form = useForm({
  name: '',
  type: 'contrato',
  notes: '',
  file: null,
})

const resetForm = () => {
  form.reset()
  form.type = 'contrato'
  form.file = null
  form.clearErrors()
}

watch(() => props.show, (visible) => {
  if (visible) resetForm()
})

const onFileChange = (event) => {
  form.file = event.target.files?.[0] ?? null
}

const close = () => emit('close')
const submit = () => emit('submit', form)
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm" @click.self="close">
      <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b border-[#E5E5E5] p-5">
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#007AFF]">Documentos</p>
            <h2 class="font-display text-lg font-bold text-[#1D1D1F]">Cargar documento</h2>
          </div>
          <button type="button" class="rounded-lg p-2 text-[#8E8E93] hover:bg-[#F5F5F7]" @click="close"><X class="size-5" /></button>
        </div>

        <form class="space-y-4 p-5" @submit.prevent="submit">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Nombre del documento</label>
            <input v-model="form.name" type="text" required class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50" placeholder="Ej. Contrato laboral 2026">
            <p v-if="form.errors.name" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Tipo de documento</label>
            <select v-model="form.type" required class="w-full rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50">
              <option v-for="option in documentTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Archivo</label>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required class="w-full text-sm text-[#6E6E73]" @change="onFileChange">
            <p v-if="form.errors.file" class="mt-1 text-xs text-[#FF3B30]">{{ form.errors.file }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-medium text-[#1D1D1F]">Notas (opcional)</label>
            <textarea v-model="form.notes" rows="2" class="w-full resize-none rounded-xl border-none bg-[#F5F5F7] px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#007AFF]/50" />
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
