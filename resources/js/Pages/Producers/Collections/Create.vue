<script setup>
import AppShell from '../../../Components/AppShell.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { ArrowLeft, Droplets, Building, Factory, MapPin, User, Calendar, Thermometer, FileText, Check } from '@lucide/vue'

const form = useForm({
    company_id: '',
    plant_id: '',
    route_id: '',
    producer_id: '',
    collection_date: '',
    liters: '',
    temperature: '',
    acidity: '',
    fat_percentage: '',
    notes: '',
})

const submit = () => {
    form.post('/collections')
}
</script>

<template>
  <AppShell>
    <div class="mb-8">
      <Link href="/collections" class="inline-flex items-center text-[#007AFF] hover:text-[#0056CC] mb-4">
        <ArrowLeft class="w-4 h-4 mr-2" />
        Volver a acopio
      </Link>
      <h1 class="text-3xl font-display font-bold text-[#1D1D1F]">Registrar Acopio de Leche</h1>
      <p class="text-[#6E6E73] mt-1">Registra la recolección diaria de leche de un productor</p>
    </div>

    <div v-if="$page.props.flash.error" class="bg-[#FFE5E5] border border-[#FF3B30] text-[#FF3B30] px-4 py-3 rounded-xl mb-6">
      {{ $page.props.flash.error }}
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-2xl border border-[#E5E5E5] p-8 shadow-sm">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Empresa</label>
          <div class="relative">
            <Building class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input 
              type="text" 
              v-model="form.company_id" 
              placeholder="Seleccionar empresa"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-3 text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Planta</label>
          <div class="relative">
            <Factory class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input 
              type="text" 
              v-model="form.plant_id" 
              placeholder="Seleccionar planta"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-3 text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Ruta</label>
          <div class="relative">
            <MapPin class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input 
              type="text" 
              v-model="form.route_id" 
              placeholder="Seleccionar ruta"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-3 text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Productor</label>
          <div class="relative">
            <User class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input 
              type="text" 
              v-model="form.producer_id" 
              placeholder="Seleccionar productor"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-3 text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Fecha de Acopio</label>
          <div class="relative">
            <Calendar class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input 
              type="date" 
              v-model="form.collection_date"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-3 text-[#1D1D1F]"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Litros</label>
          <div class="relative">
            <Droplets class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input 
              type="number" 
              step="0.01" 
              v-model="form.liters" 
              placeholder="0.00"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-3 text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Temperatura (°C)</label>
          <div class="relative">
            <Thermometer class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input 
              type="number" 
              step="0.01" 
              v-model="form.temperature" 
              placeholder="0.00"
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-3 text-[#1D1D1F] placeholder-[#8E8E93]"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Acidez</label>
          <input 
            type="number" 
            step="0.01" 
            v-model="form.acidity" 
            placeholder="0.00"
            class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-3 text-[#1D1D1F] placeholder-[#8E8E93]"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Porcentaje de Grasa (%)</label>
          <input 
            type="number" 
            step="0.01" 
            v-model="form.fat_percentage" 
            placeholder="0.00"
            class="w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-3 text-[#1D1D1F] placeholder-[#8E8E93]"
          />
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-[#1D1D1F] mb-2">Notas</label>
          <div class="relative">
            <FileText class="absolute left-3 top-3 w-4 h-4 text-[#8E8E93]" />
            <textarea 
              v-model="form.notes" 
              rows="3" 
              placeholder="Observaciones sobre el acopio..."
              class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-3 px-3 resize-none text-[#1D1D1F] placeholder-[#8E8E93]"
            ></textarea>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-[#E5E5E5]">
        <Link 
          href="/collections" 
          class="px-5 py-2.5 border border-[#E5E5E5] rounded-xl hover:bg-[#F5F5F7] text-[#1D1D1F] font-medium transition-colors"
        >
          Cancelar
        </Link>
        <button 
          type="submit" 
          :disabled="form.processing" 
          class="inline-flex items-center px-5 py-2.5 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] disabled:opacity-50 transition-colors font-medium shadow-sm"
        >
          <Check v-if="!form.processing" class="w-4 h-4 mr-2" />
          {{ form.processing ? 'Guardando...' : 'Registrar acopio' }}
        </button>
      </div>
    </form>
  </AppShell>
</template>
