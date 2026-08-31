<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { BadgeCheck, Check, Pencil, Power, PowerOff, Search, UsersRound, X } from '@lucide/vue'
import AppShell from '../../Components/AppShell.vue'
import EmployeeModal from '../../Components/EmployeeModal.vue'
import PersonnelStatsPanel from '../../Components/PersonnelStatsPanel.vue'

const props = defineProps({
  employees: Object,
  roles: Array,
  stats: Object,
  plants: Array,
  statusOptions: Array,
  areaOptions: Array,
  contractTypeOptions: Array,
  paymentMethodOptions: Array,
})

const search = ref('')
const roleFilter = ref('all')
const statusFilter = ref('all')
const showEmployee = ref(false)
const showRole = ref(false)
const success = ref('')
const editingEmployee = ref(null)
const editingRoleId = ref(null)
const pendingRoleId = ref(null)
const pendingEmployeeId = ref(null)

const roleForm = useForm({ name: '', description: '' })

const filteredEmployees = computed(() => {
  const term = search.value.trim().toLocaleLowerCase()

  return props.employees.data.filter((employee) => {
    const content = [employee.full_name, employee.code, employee.email, employee.phone, employee.role?.name, employee.area]
      .filter(Boolean).join(' ').toLocaleLowerCase()
    const roleMatch = roleFilter.value === 'all' || String(employee.role?.id) === roleFilter.value
    const statusMatch = statusFilter.value === 'all' || employee.status === statusFilter.value
    return roleMatch && statusMatch && (!term || content.includes(term))
  })
})

const statusClass = (status) => ({
  activo: 'bg-[#E9F8EE] text-[#187A31]',
  suspendido: 'bg-[#FFF4E5] text-[#FF9500]',
  retirado: 'bg-[#F2F2F7] text-[#6E6E73]',
}[status] || 'bg-[#F2F2F7] text-[#6E6E73]')

const initials = (name) => name.split(' ').slice(0, 2).map((part) => part[0]).join('').toUpperCase()

const openEmployee = (employee = null) => {
  editingEmployee.value = employee
  showEmployee.value = true
}

const submitEmployee = (form) => {
  const wasEditing = Boolean(editingEmployee.value?.id)
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      showEmployee.value = false
      editingEmployee.value = null
      success.value = wasEditing ? 'Colaborador actualizado.' : 'Colaborador registrado.'
    },
    onError: () => {
      success.value = ''
    },
  }

  if (wasEditing) {
    form.put(`/employees/${editingEmployee.value.id}`, options)
    return
  }

  form.post('/employees', options)
}

const cycleStatus = (employee) => {
  const next = employee.status === 'activo' ? 'suspendido' : employee.status === 'suspendido' ? 'retirado' : 'activo'
  pendingEmployeeId.value = employee.id
  router.patch(`/employees/${employee.id}/status`, { status: next }, {
    preserveScroll: true,
    onSuccess: () => { success.value = 'Estado actualizado correctamente.' },
    onFinish: () => { pendingEmployeeId.value = null },
  })
}

const openRole = (role = null) => {
  roleForm.reset()
  roleForm.clearErrors()
  editingRoleId.value = role?.id ?? null
  roleForm.name = role?.name ?? ''
  roleForm.description = role?.description ?? ''
  showRole.value = true
}

const submitRole = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      showRole.value = false
      roleForm.reset()
      success.value = editingRoleId.value ? 'Rol actualizado.' : 'Rol creado.'
      editingRoleId.value = null
    },
  }

  if (editingRoleId.value) {
    roleForm.put(`/employees/roles/${editingRoleId.value}`, options)
    return
  }

  roleForm.post('/employees/roles', options)
}

const toggleRole = (role) => {
  pendingRoleId.value = role.id
  router.patch(`/employees/roles/${role.id}/status`, { active: !role.active }, {
    preserveScroll: true,
    onFinish: () => { pendingRoleId.value = null },
  })
}
const filterByStatus = (status) => {
  statusFilter.value = status
}
</script>

<template>
  <AppShell>
    <Head title="Personal" />
    <div class="mx-auto max-w-7xl">
      <PersonnelStatsPanel
        :stats="stats"
        :roles="roles"
        :pending-role-id="pendingRoleId"
        @agregar-colaborador="openEmployee()"
        @nuevo-rol="openRole()"
        @filtrar-estado="filterByStatus"
        @editar-rol="openRole"
        @toggle-rol="toggleRole"
      />

      <div v-if="success" class="mb-4 flex items-center justify-between rounded-xl bg-[#E9F8EE] px-4 py-3 text-sm text-[#187A31]">
        <span class="flex items-center gap-2"><Check class="size-4" />{{ success }}</span>
        <button type="button" @click="success = ''"><X class="size-3.5" /></button>
      </div>

      <section class="overflow-hidden rounded-[28px] border border-[#E5E5E5] bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-[#E5E5E5] p-4 lg:flex-row lg:justify-between">
          <div class="relative lg:w-96">
            <Search class="absolute left-3 top-3 size-4 text-[#8E8E93]" />
            <input v-model="search" type="search" placeholder="Buscar por nombre, código o cédula..." class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] pl-9 pr-3 text-sm focus:ring-2 focus:ring-[#007AFF]/20">
          </div>
          <div class="flex gap-2">
            <select v-model="roleFilter" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-xs font-semibold text-[#6E6E73]">
              <option value="all">Todos los roles</option>
              <option v-for="role in roles" :key="role.id" :value="String(role.id)">{{ role.name }}</option>
            </select>
            <select v-model="statusFilter" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-xs font-semibold text-[#6E6E73]">
              <option value="all">Todos los estados</option>
              <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </div>
        </div>

        <div v-if="filteredEmployees.length" class="divide-y divide-[#EFEFF1]">
          <article v-for="employee in filteredEmployees" :key="employee.id" class="grid gap-3 p-4 hover:bg-[#F8FAFF] md:grid-cols-[minmax(13rem,1.5fr)_1fr_1fr_auto] md:items-center">
            <Link :href="`/employees/${employee.id}`" class="flex min-w-0 items-center gap-3">
              <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#007AFF] text-xs font-bold text-white">{{ initials(employee.full_name) }}</div>
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-[#1D1D1F]">{{ employee.full_name }}</p>
                <p class="truncate text-xs text-[#8E8E93]">{{ employee.code }} · {{ employee.identity_number || 'Sin cédula' }}</p>
              </div>
            </Link>
            <div><p class="text-sm font-medium">{{ employee.role?.name }}</p><p class="text-xs text-[#8E8E93]">{{ employee.area || 'Sin área' }}</p></div>
            <div><p class="truncate text-xs text-[#6E6E73]">{{ employee.plant?.name || 'Sin planta' }}</p></div>
            <div class="flex items-center justify-between gap-2 md:justify-end">
              <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', statusClass(employee.status)]">{{ employee.status_label }}</span>
              <span class="flex gap-1">
                <Link :href="`/employees/${employee.id}`" class="rounded-lg border border-[#E5E5E5] p-1.5 text-[#6E6E73] hover:bg-white hover:text-[#007AFF]" title="Ver ficha"><UsersRound class="size-3.5" /></Link>
                <button type="button" class="rounded-lg border border-[#E5E5E5] p-1.5 text-[#6E6E73] hover:bg-white hover:text-[#007AFF]" @click="openEmployee(employee)"><Pencil class="size-3.5" /></button>
                <button type="button" :disabled="pendingEmployeeId === employee.id" class="rounded-lg border border-[#E5E5E5] p-1.5 text-[#6E6E73] hover:bg-white disabled:opacity-40" @click="cycleStatus(employee)"><Power class="size-3.5" /></button>
              </span>
            </div>
          </article>
        </div>
        <div v-else class="flex flex-col items-center py-14 text-center">
          <UsersRound class="size-10 text-[#C7C7CC]" />
          <p class="mt-3 text-sm font-semibold">No hay colaboradores para mostrar</p>
          <button type="button" class="mt-3 text-xs font-semibold text-[#007AFF]" @click="openEmployee()">Registrar colaborador</button>
        </div>
      </section>
    </div>

    <EmployeeModal
      :show="showEmployee"
      :employee="editingEmployee"
      :roles="roles"
      :plants="plants"
      :status-options="statusOptions"
      :area-options="areaOptions"
      :contract-type-options="contractTypeOptions"
      :payment-method-options="paymentMethodOptions"
      @close="showEmployee = false"
      @submit="submitEmployee"
    />

    <div v-if="showRole" class="fixed inset-0 z-[80] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" @click.self="showRole = false">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitRole">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-[#F8F2FC] text-[#AF52DE]"><BadgeCheck class="size-5" /></div>
            <div><h2 class="text-lg font-bold">{{ editingRoleId ? 'Editar rol' : 'Nuevo rol' }}</h2></div>
          </div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="showRole = false"><X class="size-4" /></button>
        </header>
        <div class="space-y-4 p-5">
          <div><label class="mb-1 block text-xs font-semibold">Nombre del rol</label><input v-model="roleForm.name" required class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"></div>
          <div><label class="mb-1 block text-xs font-semibold">Descripción</label><textarea v-model="roleForm.description" rows="3" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" /></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4">
          <button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="showRole = false">Cancelar</button>
          <button type="submit" :disabled="roleForm.processing" class="h-10 rounded-xl bg-[#1D1D1F] px-4 text-sm font-semibold text-white disabled:opacity-50">Guardar</button>
        </footer>
      </form>
    </div>
  </AppShell>
</template>
