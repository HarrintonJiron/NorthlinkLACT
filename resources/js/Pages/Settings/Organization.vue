<script setup>
import AppShell from '../../Components/AppShell.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Building2, MapPinned, Pencil, Plus, Power, X } from '@lucide/vue'
import { ref } from 'vue'

const props = defineProps({ company: { type: Object, required: true } })
const companyForm = useForm({ name: props.company.name, legal_name: props.company.legal_name, tax_id: props.company.tax_id, address: props.company.address || '', phone: props.company.phone || '', email: props.company.email || '', currency: props.company.currency || 'NIO' })
const plantForm = useForm({ name: '', code: '', address: '', phone: '', email: '' })
const editingPlant = ref(null)
const editPlantForm = useForm({ name: '', code: '', address: '', phone: '', email: '', active: true })

const saveCompany = () => companyForm.put(`/settings/organization/${props.company.id}`, { preserveScroll: true })
const createPlant = () => plantForm.post('/settings/plants', { preserveScroll: true, onSuccess: () => plantForm.reset() })
const togglePlant = (plant) => router.patch(`/settings/plants/${plant.id}/status`, {}, { preserveScroll: true })
const editPlant = (plant) => {
  editingPlant.value = plant
  editPlantForm.name = plant.name || ''
  editPlantForm.code = plant.code || ''
  editPlantForm.address = plant.address || ''
  editPlantForm.phone = plant.phone || ''
  editPlantForm.email = plant.email || ''
  editPlantForm.active = Boolean(plant.active)
}
const savePlant = () => editPlantForm.put(`/settings/plants/${editingPlant.value.id}`, { preserveScroll: true, onSuccess: () => { editingPlant.value = null } })
</script>

<template>
  <AppShell>
    <Head title="Empresa y sedes" />
    <div class="mx-auto max-w-6xl space-y-5">
      <header><h1 class="font-display text-3xl font-bold text-slate-950">Empresa y sedes</h1><p class="mt-1 text-sm text-slate-600">Información oficial de Lácteos Quebar, oficina y planta de producción.</p></header>
      <div v-if="$page.props.flash?.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ $page.props.flash.success }}</div>

      <form class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="saveCompany">
        <div class="flex items-center gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><Building2 class="size-5" /></div><div><h2 class="font-bold">Datos de la empresa</h2><p class="text-xs text-slate-500">Se mostrarán en reportes y documentos administrativos.</p></div></div>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <label class="text-sm font-semibold">Nombre comercial<input v-model="companyForm.name" class="mt-1 min-h-11 w-full rounded-xl border-slate-200" required></label>
          <label class="text-sm font-semibold">Razón social<input v-model="companyForm.legal_name" class="mt-1 min-h-11 w-full rounded-xl border-slate-200" required></label>
          <label class="text-sm font-semibold">RUC / identificación fiscal<input v-model="companyForm.tax_id" class="mt-1 min-h-11 w-full rounded-xl border-slate-200" required></label>
          <label class="text-sm font-semibold">Teléfono<input v-model="companyForm.phone" class="mt-1 min-h-11 w-full rounded-xl border-slate-200"></label>
          <label class="text-sm font-semibold">Correo<input v-model="companyForm.email" type="email" class="mt-1 min-h-11 w-full rounded-xl border-slate-200"></label>
          <label class="text-sm font-semibold">Moneda<select v-model="companyForm.currency" class="mt-1 min-h-11 w-full rounded-xl border-slate-200"><option value="NIO">Córdoba (NIO)</option><option value="USD">Dólar (USD)</option></select></label>
          <label class="text-sm font-semibold sm:col-span-2 lg:col-span-3">Dirección<input v-model="companyForm.address" class="mt-1 min-h-11 w-full rounded-xl border-slate-200"></label>
        </div>
        <button class="mt-4 min-h-11 rounded-xl bg-slate-950 px-5 text-sm font-bold text-white" :disabled="companyForm.processing">Guardar empresa</button>
      </form>

      <section class="grid gap-5 lg:grid-cols-[1fr_360px]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center gap-3"><MapPinned class="size-5 text-blue-700" /><h2 class="font-bold">Sedes registradas</h2></div><div class="mt-4 space-y-3"><article v-for="plant in company.plants" :key="plant.id" class="flex flex-col justify-between gap-4 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center"><div><p class="font-bold">{{ plant.name }}</p><p class="text-xs text-slate-500">{{ plant.code }} · {{ plant.address || 'Sin dirección' }}</p><p class="mt-1 text-xs text-slate-500">{{ plant.phone || 'Sin teléfono' }} · {{ plant.email || 'Sin correo' }}</p></div><div class="flex gap-2"><button class="inline-flex min-h-9 items-center gap-2 rounded-lg bg-blue-50 px-3 text-xs font-bold text-blue-700" @click="editPlant(plant)"><Pencil class="size-3.5" />Editar</button><button :class="['inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-xs font-bold', plant.active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600']" @click="togglePlant(plant)"><Power class="size-3.5" />{{ plant.active ? 'Activa' : 'Inactiva' }}</button></div></article></div></div>
        <form class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm" @submit.prevent="createPlant"><div class="flex items-center gap-2"><Plus class="size-5 text-blue-700" /><h2 class="font-bold">Nueva sede</h2></div><div class="mt-4 space-y-3"><input v-model="plantForm.name" placeholder="Nombre" class="min-h-11 w-full rounded-xl border-slate-200" required><input v-model="plantForm.code" placeholder="Código, ej. WAS-OFI" class="min-h-11 w-full rounded-xl border-slate-200 uppercase" required><input v-model="plantForm.address" placeholder="Dirección" class="min-h-11 w-full rounded-xl border-slate-200"><input v-model="plantForm.phone" placeholder="Teléfono" class="min-h-11 w-full rounded-xl border-slate-200"><input v-model="plantForm.email" type="email" placeholder="Correo" class="min-h-11 w-full rounded-xl border-slate-200"></div><button class="mt-4 min-h-11 w-full rounded-xl bg-blue-600 text-sm font-bold text-white" :disabled="plantForm.processing">Registrar sede</button></form>
      </section>

      <div v-if="editingPlant" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @click.self="editingPlant = null">
        <form class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl" @submit.prevent="savePlant">
          <div class="flex items-center justify-between"><div><p class="text-xs font-bold uppercase tracking-wider text-blue-700">Sede</p><h2 class="text-xl font-bold">Editar {{ editingPlant.name }}</h2></div><button type="button" class="rounded-lg p-2 hover:bg-slate-100" @click="editingPlant = null"><X class="size-5" /></button></div>
          <div class="mt-4 grid gap-3 sm:grid-cols-2"><input v-model="editPlantForm.name" placeholder="Nombre" class="min-h-11 rounded-xl border-slate-200" required><input v-model="editPlantForm.code" placeholder="Código" class="min-h-11 rounded-xl border-slate-200 uppercase" required><input v-model="editPlantForm.address" placeholder="Dirección" class="min-h-11 rounded-xl border-slate-200 sm:col-span-2"><input v-model="editPlantForm.phone" placeholder="Teléfono" class="min-h-11 rounded-xl border-slate-200"><input v-model="editPlantForm.email" type="email" placeholder="Correo" class="min-h-11 rounded-xl border-slate-200"></div>
          <label class="mt-3 flex items-center gap-2 text-sm"><input v-model="editPlantForm.active" type="checkbox" class="rounded"> Sede activa</label>
          <button class="mt-4 min-h-11 w-full rounded-xl bg-blue-600 font-bold text-white" :disabled="editPlantForm.processing">Guardar cambios</button>
        </form>
      </div>
    </div>
  </AppShell>
</template>
