<script setup>
import AppShell from '../../../Components/AppShell.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { ArrowLeft, MapPin, Building, Factory, FileText, Check } from '@lucide/vue'

const form = useForm({
    company_id: '',
    plant_id: '',
    code: '',
    name: '',
    description: '',
    active: true,
})

const submit = () => {
    form.post('/routes')
}
</script>

<template>
  <AppShell>
    <div class="mb-8">
      <Link href="/routes" class="inline-flex items-center text-[#1E3A5F] hover:text-[#2D5A3D] mb-4">
        <ArrowLeft class="w-4 h-4 mr-2" />
        Volver a rutas
      </Link>
      <h1 class="text-2xl font-display font-bold text-gray-900">Nueva Ruta de Acopio</h1>
      <p class="text-gray-500 mt-1">Crea una nueva ruta para organizar la recolección de leche</p>
    </div>

    <div v-if="$page.props.flash.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
      {{ $page.props.flash.error }}
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Empresa</label>
          <div class="relative">
            <Building class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input 
              type="text" 
              v-model="form.company_id" 
              placeholder="Seleccionar empresa"
              class="pl-10 w-full border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2D5A3D] focus:border-transparent py-2 px-3" 
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Planta</label>
          <div class="relative">
            <Factory class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input 
              type="text" 
              v-model="form.plant_id" 
              placeholder="Seleccionar planta"
              class="pl-10 w-full border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2D5A3D] focus:border-transparent py-2 px-3" 
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Código</label>
          <div class="relative">
            <MapPin class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input 
              type="text" 
              v-model="form.code" 
              placeholder="Ej: RUT-001"
              class="pl-10 w-full border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2D5A3D] focus:border-transparent py-2 px-3" 
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
          <input 
            type="text" 
            v-model="form.name" 
            placeholder="Ej: Ruta Matagalpa Norte"
            class="w-full border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2D5A3D] focus:border-transparent py-2 px-3" 
          />
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
          <div class="relative">
            <FileText class="absolute left-3 top-3 w-4 h-4 text-gray-400" />
            <textarea 
              v-model="form.description" 
              rows="3" 
              placeholder="Describe la ruta, zonas que cubre, etc."
              class="pl-10 w-full border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2D5A3D] focus:border-transparent py-2 px-3 resize-none"
            ></textarea>
          </div>
        </div>

        <div class="md:col-span-2">
          <label class="flex items-center space-x-3">
            <input 
              type="checkbox" 
              v-model="form.active" 
              class="rounded border-gray-300 text-[#2D5A3D] focus:ring-[#2D5A3D]" 
            />
            <span class="text-sm text-gray-700">Ruta activa</span>
          </label>
        </div>
      </div>

      <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
        <Link 
          href="/routes" 
          class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-700"
        >
          Cancelar
        </Link>
        <button 
          type="submit" 
          :disabled="form.processing" 
          class="inline-flex items-center px-4 py-2 bg-[#1E3A5F] text-white rounded-lg hover:bg-[#2D4A6F] disabled:opacity-50 transition-colors"
        >
          <Check v-if="!form.processing" class="w-4 h-4 mr-2" />
          {{ form.processing ? 'Guardando...' : 'Guardar ruta' }}
        </button>
      </div>
    </form>
  </AppShell>
</template>
