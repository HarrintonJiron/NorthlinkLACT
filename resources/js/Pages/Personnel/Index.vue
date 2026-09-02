<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { BadgeCheck, BriefcaseBusiness, CalendarDays, Check, ClipboardPlus, Eye, Mail, Pencil, Phone, Plus, Power, PowerOff, Search, UserRound, UsersRound, Wallet, X } from '@lucide/vue'
import AppShell from '../../Components/AppShell.vue'

const props = defineProps({ employees: Object, roles: Array, stats: Object })
const search = ref('')
const roleFilter = ref('all')
const showEmployee = ref(false)
const showRole = ref(false)
const success = ref('')
const editingRoleId = ref(null)
const pendingRoleId = ref(null)
const editingEmployeeId = ref(null)
const originalEmployeeRoleId = ref(null)
const pendingEmployeeId = ref(null)

const employeeForm = useForm({
  full_name: '', employee_role_id: '', identity_number: '', email: '',
  phone: '', hired_at: '', active: true, base_salary: '', pay_frequency: 'monthly',
})
const roleForm = useForm({ name: '', description: '' })
const assignableRoles = computed(() => props.roles.filter((role) =>
  role.active || role.id === originalEmployeeRoleId.value,
))

const filteredEmployees = computed(() => {
  const term = search.value.trim().toLocaleLowerCase()

  return props.employees.data.filter((employee) => {
    const content = [employee.full_name, employee.email, employee.phone, employee.role?.name]
      .filter(Boolean).join(' ').toLocaleLowerCase()
    return (roleFilter.value === 'all' || String(employee.role?.id) === roleFilter.value) && (!term || content.includes(term))
  })
})

const openEmployee = (employee = null) => {
  employeeForm.reset()
  employeeForm.clearErrors()
  editingEmployeeId.value = employee?.id ?? null
  originalEmployeeRoleId.value = employee?.role?.id ?? null
  employeeForm.full_name = employee?.full_name ?? ''
  employeeForm.employee_role_id = employee?.role?.id ?? ''
  employeeForm.identity_number = employee?.identity_number ?? ''
  employeeForm.email = employee?.email ?? ''
  employeeForm.phone = employee?.phone ?? ''
  employeeForm.hired_at = employee?.hired_at?.slice(0, 10) ?? ''
  employeeForm.active = employee?.active ?? true
  employeeForm.base_salary = employee?.base_salary ?? ''
  employeeForm.pay_frequency = employee?.pay_frequency ?? 'monthly'
  showEmployee.value = true
}
const closeEmployee = () => {
  if (!employeeForm.processing) {
    showEmployee.value = false
    editingEmployeeId.value = null
    originalEmployeeRoleId.value = null
  }
}
const submitEmployee = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      showEmployee.value = false
      employeeForm.reset()
      success.value = editingEmployeeId.value
        ? 'Colaborador actualizado correctamente.'
        : 'Colaborador registrado correctamente.'
      editingEmployeeId.value = null
      originalEmployeeRoleId.value = null
    },
  }

  if (editingEmployeeId.value) {
    employeeForm.put(`/employees/${editingEmployeeId.value}`, options)
    return
  }

  employeeForm.post('/employees', options)
}
const toggleEmployee = (employee) => {
  pendingEmployeeId.value = employee.id
  router.patch(`/employees/${employee.id}/status`, { active: !employee.active }, {
    preserveScroll: true,
    onSuccess: () => {
      success.value = employee.active
        ? 'Colaborador desactivado correctamente.'
        : 'Colaborador activado correctamente.'
    },
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
const closeRole = () => {
  if (!roleForm.processing) {
    showRole.value = false
    editingRoleId.value = null
  }
}
const submitRole = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      showRole.value = false
      roleForm.reset()
      success.value = editingRoleId.value
        ? 'Rol actualizado correctamente.'
        : 'El nuevo rol ya está disponible.'
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
    onSuccess: () => {
      success.value = role.active
        ? 'Rol deshabilitado correctamente.'
        : 'Rol habilitado correctamente.'
    },
    onFinish: () => { pendingRoleId.value = null },
  })
}

const initials = (name) => name.split(' ').slice(0, 2).map((part) => part[0]).join('').toUpperCase()
const dateLabel = (value) => value
  ? new Intl.DateTimeFormat('es-NI', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
  : 'Sin fecha'
const tenureLabel = (days) => {
  if (!days) return 'Sin antigüedad'
  const years = Math.floor(days / 365)
  const months = Math.floor((days % 365) / 30)
  if (years > 0) return `${years} año${years > 1 ? 's' : ''}${months > 0 ? `, ${months}m` : ''}`
  if (months > 0) return `${months} mes${months > 1 ? 'es' : ''}`
  return `${days} día${days === 1 ? '' : 's'}`
}
</script>

<template>
  <AppShell>
    <Head title="Personal" />
    <div class="mx-auto max-w-7xl">
      <header class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div class="mb-2 flex items-center gap-2 text-xs font-semibold text-[#007AFF]">
            <UsersRound class="size-4" /> Gestión de talento
          </div>
          <h1 class="font-display text-2xl font-bold text-[#1D1D1F]">Personal</h1>
          <p class="mt-1 text-sm text-[#6E6E73]">Colaboradores y sus roles dentro de la organización.</p>
        </div>
        <div class="flex gap-2">
          <button type="button" class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl border border-[#DADAE0] bg-white px-4 text-sm font-semibold shadow-sm hover:bg-[#F8F2FC] sm:flex-none" @click="openRole">
            <ClipboardPlus class="size-4 text-[#AF52DE]" /> Nuevo rol
          </button>
          <button type="button" class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#0066D6] sm:flex-none" @click="openEmployee()">
            <Plus class="size-4" /> Colaborador
          </button>
        </div>
      </header>

      <div v-if="success" class="mb-4 flex items-center justify-between rounded-xl bg-[#E9F8EE] px-4 py-3 text-sm text-[#187A31]">
        <span class="flex items-center gap-2"><Check class="size-4" />{{ success }}</span>
        <button type="button" @click="success = ''"><X class="size-3.5" /></button>
      </div>

      <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div v-for="item in [
          { label: 'Colaboradores', value: stats.total, color: 'text-[#1D1D1F]' },
          { label: 'Activos', value: stats.active, color: 'text-[#34C759]' },
          { label: 'Inactivos', value: stats.inactive, color: 'text-[#8E8E93]' },
          { label: 'Roles', value: stats.roles, color: 'text-[#AF52DE]' },
        ]" :key="item.label" class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">{{ item.label }}</p>
          <p :class="['mt-1 text-xl font-bold', item.color]">{{ item.value }}</p>
        </div>
      </div>

      <section class="mb-4 rounded-2xl border border-[#E5E5E5] bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between">
          <div><h2 class="text-sm font-semibold">Roles disponibles</h2><p class="text-xs text-[#8E8E93]">Administrativo, Ruta y los cargos que agregues.</p></div>
          <button type="button" class="inline-flex items-center gap-1 text-xs font-semibold text-[#007AFF]" @click="openRole"><Plus class="size-3.5" /> Agregar</button>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
          <div v-for="role in roles" :key="role.id" :class="['group flex items-center gap-2 rounded-xl border px-2.5 py-1.5 text-xs font-semibold transition', role.active ? 'border-[#D9E8FF] bg-[#F1F6FF] text-[#245DA8]' : 'border-[#E5E5E5] bg-[#F5F5F7] text-[#8E8E93]']">
            <BriefcaseBusiness class="size-3.5" />
            <span>{{ role.name }}</span>
            <span class="rounded-md bg-white px-1.5 text-[10px] text-[#6E6E73]">{{ role.employees_count }}</span>
            <span v-if="!role.active" class="rounded-md bg-[#E5E5EA] px-1.5 text-[9px] uppercase tracking-wide text-[#6E6E73]">Inactivo</span>
            <span class="ml-1 flex gap-0.5 border-l border-current/15 pl-1.5">
              <button type="button" class="rounded-md p-1 hover:bg-white" :aria-label="`Editar rol ${role.name}`" :title="`Editar ${role.name}`" @click="openRole(role)"><Pencil class="size-3.5" /></button>
              <button type="button" :disabled="pendingRoleId === role.id" :class="['rounded-md p-1 hover:bg-white disabled:opacity-40', role.active ? 'text-[#D70015]' : 'text-[#187A31]']" :aria-label="`${role.active ? 'Deshabilitar' : 'Habilitar'} rol ${role.name}`" :title="role.active ? 'Deshabilitar rol' : 'Habilitar rol'" @click="toggleRole(role)"><PowerOff v-if="role.active" class="size-3.5" /><Power v-else class="size-3.5" /></button>
            </span>
          </div>
          <button v-if="!roles.length" type="button" class="rounded-xl border border-dashed px-3 py-2 text-xs text-[#6E6E73]" @click="openRole">Crear primer rol</button>
        </div>
      </section>

      <section class="overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-[#E5E5E5] p-4 lg:flex-row lg:justify-between">
          <div class="relative lg:w-96">
            <Search class="absolute left-3 top-3 size-4 text-[#8E8E93]" />
            <input v-model="search" type="search" placeholder="Buscar colaborador o rol..." class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] pl-9 pr-3 text-sm focus:ring-2 focus:ring-[#007AFF]/20">
          </div>
          <select v-model="roleFilter" class="h-10 rounded-xl border-0 bg-[#F5F5F7] px-3 text-xs font-semibold text-[#6E6E73] focus:ring-2 focus:ring-[#007AFF]/20">
            <option value="all">Todos los roles</option>
            <option v-for="role in roles" :key="role.id" :value="String(role.id)">{{ role.name }}</option>
          </select>
        </div>

        <div v-if="filteredEmployees.length" class="divide-y divide-[#EFEFF1]">
          <article v-for="employee in filteredEmployees" :key="employee.id" class="grid gap-3 p-4 hover:bg-[#F8FAFF] md:grid-cols-[minmax(13rem,1.5fr)_1fr_1fr_1fr_auto] md:items-center">
            <Link :href="`/employees/${employee.id}`" class="flex min-w-0 items-center gap-3">
              <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#007AFF] text-xs font-bold text-white">{{ initials(employee.full_name) }}</div>
              <div class="min-w-0"><p class="truncate text-sm font-semibold hover:text-[#007AFF]">{{ employee.full_name }}</p><p class="truncate text-xs text-[#8E8E93]">{{ employee.identity_number || 'Sin identificación' }}</p></div>
            </Link>
            <div><p class="text-sm font-medium">{{ employee.role?.name }}</p><p class="text-xs text-[#8E8E93]">Rol asignado</p></div>
            <div><p class="text-sm font-medium">{{ tenureLabel(employee.tenure_days) }}</p><p class="text-xs text-[#8E8E93]">Ingresó {{ dateLabel(employee.hired_at) }}</p></div>
            <div><p class="text-sm font-medium">{{ employee.days_worked_this_month }} días</p><p class="text-xs text-[#8E8E93]">Trabajados este mes</p></div>
            <div class="flex items-center justify-between gap-2 md:justify-end">
              <span :class="['w-fit rounded-full px-2.5 py-1 text-xs font-semibold', employee.active ? 'bg-[#E9F8EE] text-[#187A31]' : 'bg-[#F2F2F7] text-[#6E6E73]']">{{ employee.active ? 'Activo' : 'Inactivo' }}</span>
              <span class="flex gap-1">
                <Link :href="`/employees/${employee.id}`" class="rounded-lg border border-[#E5E5E5] p-1.5 text-[#6E6E73] hover:bg-white hover:text-[#007AFF]" :aria-label="`Ver ficha de ${employee.full_name}`" title="Ver ficha completa"><Eye class="size-3.5" /></Link>
                <button type="button" class="rounded-lg border border-[#E5E5E5] p-1.5 text-[#6E6E73] hover:bg-white hover:text-[#007AFF]" :aria-label="`Editar a ${employee.full_name}`" title="Editar colaborador" @click="openEmployee(employee)"><Pencil class="size-3.5" /></button>
                <button type="button" :disabled="pendingEmployeeId === employee.id" :class="['rounded-lg border border-[#E5E5E5] p-1.5 hover:bg-white disabled:opacity-40', employee.active ? 'text-[#D70015]' : 'text-[#187A31]']" :aria-label="`${employee.active ? 'Desactivar' : 'Activar'} a ${employee.full_name}`" :title="employee.active ? 'Desactivar colaborador' : 'Activar colaborador'" @click="toggleEmployee(employee)"><PowerOff v-if="employee.active" class="size-3.5" /><Power v-else class="size-3.5" /></button>
              </span>
            </div>
          </article>
        </div>
        <div v-else class="flex flex-col items-center py-14 text-center">
          <UsersRound class="size-10 text-[#C7C7CC]" /><p class="mt-3 text-sm font-semibold">No hay colaboradores para mostrar</p>
          <button type="button" class="mt-3 text-xs font-semibold text-[#007AFF]" @click="openEmployee()">Registrar colaborador</button>
        </div>
        <div v-if="employees.links?.length > 3" class="flex items-center justify-between border-t px-4 py-3">
          <p class="text-xs text-[#8E8E93]">{{ employees.from }}–{{ employees.to }} de {{ employees.total }}</p>
          <div class="flex gap-1"><Link v-for="link in employees.links.filter((item) => item.url)" :key="link.label" :href="link.url" :class="['rounded-lg px-2.5 py-1.5 text-xs', link.active ? 'bg-[#007AFF] text-white' : 'text-[#6E6E73]']" v-html="link.label" /></div>
        </div>
      </section>
    </div>

    <div v-if="showEmployee" class="fixed inset-0 z-[70] flex justify-end bg-[#1D1D1F]/35 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="closeEmployee">
      <form class="flex h-full w-full max-w-lg flex-col bg-white shadow-2xl" @submit.prevent="submitEmployee">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#E8F2FF] text-[#007AFF]"><UserRound class="size-5" /></div><div><h2 class="text-lg font-bold">{{ editingEmployeeId ? 'Editar colaborador' : 'Nuevo colaborador' }}</h2><p class="text-xs text-[#8E8E93]">{{ editingEmployeeId ? 'Actualiza sus datos y asignación.' : 'Datos y asignación laboral.' }}</p></div></div>
          <button type="button" class="p-2 text-[#8E8E93]" @click="closeEmployee"><X class="size-4" /></button>
        </header>
        <div class="flex-1 space-y-4 overflow-y-auto p-5">
          <div><label class="mb-1 block text-xs font-semibold">Nombre completo</label><input v-model="employeeForm.full_name" autofocus class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm focus:ring-2 focus:ring-[#007AFF]/20" placeholder="Ej. Carlos Martínez"><p v-if="employeeForm.errors.full_name" class="mt-1 text-xs text-[#D70015]">{{ employeeForm.errors.full_name }}</p></div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Identificación</label><input v-model="employeeForm.identity_number" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm" placeholder="Opcional"><p v-if="employeeForm.errors.identity_number" class="mt-1 text-xs text-[#D70015]">{{ employeeForm.errors.identity_number }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Teléfono</label><div class="relative"><Phone class="absolute left-3 top-3 size-4 text-[#8E8E93]" /><input v-model="employeeForm.phone" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] pl-9 pr-3 text-sm" placeholder="8888 8888"></div></div>
          </div>
          <div><label class="mb-1 block text-xs font-semibold">Correo</label><div class="relative"><Mail class="absolute left-3 top-3 size-4 text-[#8E8E93]" /><input v-model="employeeForm.email" type="email" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] pl-9 pr-3 text-sm" placeholder="Opcional"></div><p v-if="employeeForm.errors.email" class="mt-1 text-xs text-[#D70015]">{{ employeeForm.errors.email }}</p></div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Rol</label><select v-model="employeeForm.employee_role_id" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><option value="">Seleccionar</option><option v-for="role in assignableRoles" :key="role.id" :value="role.id">{{ role.name }}{{ role.active ? '' : ' (inactivo)' }}</option></select><p v-if="employeeForm.errors.employee_role_id" class="mt-1 text-xs text-[#D70015]">{{ employeeForm.errors.employee_role_id }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Fecha de ingreso</label><div class="relative"><CalendarDays class="absolute left-3 top-3 size-4 text-[#8E8E93]" /><input v-model="employeeForm.hired_at" type="date" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] pl-9 pr-3 text-sm"></div><p v-if="employeeForm.errors.hired_at" class="mt-1 text-xs text-[#D70015]">{{ employeeForm.errors.hired_at }}</p></div>
          </div>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-semibold">Sueldo base</label><div class="relative"><Wallet class="absolute left-3 top-3 size-4 text-[#8E8E93]" /><input v-model="employeeForm.base_salary" type="number" step="0.01" min="0" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] pl-9 pr-3 text-sm" placeholder="Opcional"></div><p v-if="employeeForm.errors.base_salary" class="mt-1 text-xs text-[#D70015]">{{ employeeForm.errors.base_salary }}</p></div>
            <div><label class="mb-1 block text-xs font-semibold">Frecuencia de pago</label><select v-model="employeeForm.pay_frequency" class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm"><option value="weekly">Semanal</option><option value="biweekly">Quincenal</option><option value="monthly">Mensual</option></select><p v-if="employeeForm.errors.pay_frequency" class="mt-1 text-xs text-[#D70015]">{{ employeeForm.errors.pay_frequency }}</p></div>
          </div>
          <button type="button" class="text-xs font-semibold text-[#007AFF]" @click="closeEmployee(); openRole()">¿No aparece el rol? Créalo aquí</button>
          <div class="flex items-center justify-between rounded-xl bg-[#F5F5F7] p-3"><div><p class="text-sm font-semibold">Colaborador activo</p><p class="text-xs text-[#8E8E93]">Disponible para asignaciones.</p></div><button type="button" :class="['relative h-6 w-11 rounded-full', employeeForm.active ? 'bg-[#34C759]' : 'bg-[#C7C7CC]']" @click="employeeForm.active = !employeeForm.active"><span :class="['absolute top-0.5 size-5 rounded-full bg-white shadow', employeeForm.active ? 'left-5.5' : 'left-0.5']" /></button></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4"><button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="closeEmployee">Cancelar</button><button type="submit" :disabled="employeeForm.processing || !assignableRoles.length" class="h-10 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ employeeForm.processing ? 'Guardando...' : (editingEmployeeId ? 'Guardar cambios' : 'Crear colaborador') }}</button></footer>
      </form>
    </div>

    <div v-if="showRole" class="fixed inset-0 z-[80] flex items-center justify-center bg-[#1D1D1F]/40 p-4 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="closeRole">
      <form class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" @submit.prevent="submitRole">
        <header class="flex items-start justify-between border-b px-5 py-4"><div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#F8F2FC] text-[#AF52DE]"><BadgeCheck class="size-5" /></div><div><h2 class="text-lg font-bold">{{ editingRoleId ? 'Editar rol' : 'Nuevo rol' }}</h2><p class="text-xs text-[#8E8E93]">{{ editingRoleId ? 'Actualiza el nombre o la descripción.' : 'Crea un cargo para el personal.' }}</p></div></div><button type="button" class="p-2 text-[#8E8E93]" @click="closeRole"><X class="size-4" /></button></header>
        <div class="space-y-4 p-5">
          <div><label class="mb-1 block text-xs font-semibold">Nombre del rol</label><input v-model="roleForm.name" autofocus class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] px-3 text-sm focus:ring-2 focus:ring-[#AF52DE]/20" placeholder="Ej. Responsable de acopio"><p v-if="roleForm.errors.name" class="mt-1 text-xs text-[#D70015]">{{ roleForm.errors.name }}</p></div>
          <div><label class="mb-1 block text-xs font-semibold">Descripción <span class="font-normal text-[#8E8E93]">(opcional)</span></label><textarea v-model="roleForm.description" rows="3" maxlength="255" class="w-full resize-none rounded-xl border-0 bg-[#F5F5F7] p-3 text-sm" placeholder="Responsabilidad principal del rol" /><p v-if="roleForm.errors.description" class="mt-1 text-xs text-[#D70015]">{{ roleForm.errors.description }}</p></div>
        </div>
        <footer class="flex justify-end gap-2 border-t p-4"><button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="closeRole">Cancelar</button><button type="submit" :disabled="roleForm.processing" class="h-10 rounded-xl bg-[#1D1D1F] px-4 text-sm font-semibold text-white disabled:opacity-50">{{ roleForm.processing ? 'Guardando...' : (editingRoleId ? 'Guardar cambios' : 'Crear rol') }}</button></footer>
      </form>
    </div>
  </AppShell>
</template>
