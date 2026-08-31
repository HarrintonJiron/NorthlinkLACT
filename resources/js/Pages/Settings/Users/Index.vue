<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import {
  ArrowLeft,
  BadgeCheck,
  Check,
  Edit,
  Eye,
  EyeOff,
  KeyRound,
  LockKeyhole,
  Plus,
  Search,
  UserRound,
  Users,
  X,
} from '@lucide/vue'
import AppShell from '../../../Components/AppShell.vue'

const props = defineProps({
  users: Object,
  availableEmployees: Array,
  stats: Object,
})

const searchQuery = ref('')
const showCreatePanel = ref(false)
const showEditPanel = ref(false)
const showPassword = ref(false)
const showPin = ref(false)
const showEditPassword = ref(false)
const showEditPin = ref(false)
const successMessage = ref('')
const editingUser = ref(null)

const form = useForm({
  employee_id: '',
  username: '',
  password: '',
  password_confirmation: '',
  pin: '',
  active: true,
})

const selectedEmployee = computed(() => props.availableEmployees.find(
  (employee) => String(employee.id) === String(form.employee_id),
))

const filteredUsers = computed(() => {
  const query = searchQuery.value.trim().toLocaleLowerCase()

  if (!query) return props.users.data

  return props.users.data.filter((user) => [
    user.name,
    user.username,
    user.email,
    user.employee?.role?.name,
  ].filter(Boolean).join(' ').toLocaleLowerCase().includes(query))
})

const openCreate = () => {
  successMessage.value = ''
  form.reset()
  form.clearErrors()
  form.active = true
  showPassword.value = false
  showPin.value = false
  showCreatePanel.value = true
}

const closeCreate = () => {
  if (!form.processing) {
    showCreatePanel.value = false
    form.clearErrors()
  }
}

const submit = () => form.post('/settings/users', {
  preserveScroll: true,
  onSuccess: () => {
    showCreatePanel.value = false
    form.reset()
    successMessage.value = 'La cuenta fue creada y vinculada al colaborador.'
  },
})

const editForm = useForm({
  username: '',
  password: '',
  password_confirmation: '',
  pin: '',
  active: true,
})

const openEdit = (user) => {
  successMessage.value = ''
  editingUser.value = user
  editForm.username = user.username
  editForm.password = ''
  editForm.password_confirmation = ''
  editForm.pin = ''
  editForm.active = user.active
  showEditPassword.value = false
  showEditPin.value = false
  showEditPanel.value = true
}

const closeEdit = () => {
  if (!editForm.processing) {
    showEditPanel.value = false
    editForm.clearErrors()
    editingUser.value = null
  }
}

const submitEdit = () => editForm.put(`/settings/users/${editingUser.value.id}`, {
  preserveScroll: true,
  onSuccess: () => {
    showEditPanel.value = false
    editForm.reset()
    editingUser.value = null
    successMessage.value = 'El usuario fue actualizado exitosamente.'
  },
})

const toggleStatus = (user) => {
  const form = useForm({ active: !user.active })
  form.patch(`/settings/users/${user.id}/status`, {
    preserveScroll: true,
    onSuccess: () => {
      successMessage.value = user.active
        ? 'Usuario desactivado exitosamente.'
        : 'Usuario activado exitosamente.'
    },
  })
}

const initials = (name) => name
  .split(' ')
  .slice(0, 2)
  .map((part) => part.charAt(0))
  .join('')
  .toLocaleUpperCase()

const formatDate = (value) => new Intl.DateTimeFormat('es-NI', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
}).format(new Date(value))
</script>

<template>
  <AppShell>
    <Head title="Usuarios" />

    <div class="mx-auto max-w-7xl">
      <header class="mb-5">
        <Link href="/settings" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-[#6E6E73] transition hover:text-[#007AFF]">
          <ArrowLeft class="size-3.5" /> Configuración
        </Link>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-[#1D1D1F]">Usuarios y acceso</h1>
            <p class="mt-1 text-sm text-[#6E6E73]">Asigna credenciales a los colaboradores registrados en Personal.</p>
          </div>
          <button type="button" :disabled="!availableEmployees.length" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0066D6] disabled:cursor-not-allowed disabled:opacity-50" @click="openCreate">
            <Plus class="size-4" /> Asignar usuario
          </button>
        </div>
      </header>

      <div v-if="successMessage" class="mb-4 flex items-center justify-between gap-3 rounded-xl border border-[#34C759]/20 bg-[#E9F8EE] px-4 py-3 text-sm text-[#187A31]">
        <span class="flex items-center gap-2"><Check class="size-4" />{{ successMessage }}</span>
        <button type="button" class="rounded-md p-1 hover:bg-white/60" @click="successMessage = ''"><X class="size-3.5" /></button>
      </div>

      <div class="mb-4 grid grid-cols-3 gap-3">
        <div v-for="item in [
          { label: 'Usuarios', value: stats.total, color: 'text-[#1D1D1F]' },
          { label: 'Activos', value: stats.active, color: 'text-[#34C759]' },
          { label: 'Sin usuario', value: stats.available, color: 'text-[#AF52DE]' },
        ]" :key="item.label" class="rounded-2xl border border-[#E5E5E5] bg-white px-4 py-3 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">{{ item.label }}</p>
          <p :class="['mt-1 font-display text-xl font-bold', item.color]">{{ item.value }}</p>
        </div>
      </div>

      <section class="overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-[#E5E5E5] p-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="relative w-full sm:max-w-sm">
            <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-[#8E8E93]" />
            <input v-model="searchQuery" type="search" placeholder="Buscar colaborador, usuario o rol..." class="h-10 w-full rounded-xl border-0 bg-[#F5F5F7] pl-9 pr-3 text-sm focus:ring-2 focus:ring-[#007AFF]/20">
          </div>
          <p class="text-xs text-[#8E8E93]">{{ filteredUsers.length }} de {{ users.total }} usuarios</p>
        </div>

        <div v-if="filteredUsers.length" class="hidden overflow-x-auto md:block">
          <table class="w-full text-left">
            <thead class="bg-[#FAFAFA]">
              <tr class="text-[11px] font-semibold uppercase tracking-wide text-[#8E8E93]">
                <th class="px-4 py-3">Colaborador</th>
                <th class="px-4 py-3">Usuario</th>
                <th class="px-4 py-3">Rol de personal</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Creado</th>
                <th class="px-4 py-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#EFEFF1]">
              <tr v-for="user in filteredUsers" :key="user.id" class="transition hover:bg-[#F8FAFF]">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#007AFF] to-[#5AC8FA] text-xs font-bold text-white">{{ initials(user.name) }}</div>
                    <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ user.name }}</p><p class="truncate text-xs text-[#8E8E93]">{{ user.email || user.phone || 'Sin contacto' }}</p></div>
                  </div>
                </td>
                <td class="px-4 py-3"><span class="font-mono text-sm font-semibold text-[#245DA8]">{{ user.username || 'Sin usuario' }}</span></td>
                <td class="px-4 py-3"><span class="inline-flex rounded-lg bg-[#F2EAFE] px-2.5 py-1 text-xs font-medium text-[#7A35A8]">{{ user.employee?.role?.name || 'Sin rol' }}</span></td>
                <td class="px-4 py-3"><span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', user.active ? 'bg-[#E9F8EE] text-[#187A31]' : 'bg-[#F2F2F7] text-[#6E6E73]']">{{ user.active ? 'Activo' : 'Inactivo' }}</span></td>
                <td class="px-4 py-3 text-xs text-[#8E8E93]">{{ formatDate(user.created_at) }}</td>
                <td class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-lg p-2 text-[#6E6E73] hover:bg-[#F5F5F7] hover:text-[#007AFF]" @click="openEdit(user)" title="Editar">
                      <Edit class="size-4" />
                    </button>
                    <button type="button" :class="['rounded-lg p-2', user.active ? 'text-[#6E6E73] hover:bg-[#F5F5F7] hover:text-[#FF3B30]' : 'text-[#6E6E73] hover:bg-[#F5F5F7] hover:text-[#34C759]']" @click="toggleStatus(user)" :title="user.active ? 'Desactivar' : 'Activar'">
                      <X v-if="user.active" class="size-4" />
                      <Check v-else class="size-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredUsers.length" class="divide-y divide-[#EFEFF1] md:hidden">
          <article v-for="user in filteredUsers" :key="user.id" class="flex items-center gap-3 p-4">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#007AFF] text-xs font-bold text-white">{{ initials(user.name) }}</div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold">{{ user.name }}</p>
              <p class="truncate font-mono text-xs text-[#245DA8]">@{{ user.username }}</p>
              <div class="mt-1 flex gap-2 text-[11px]"><span class="text-[#7A35A8]">{{ user.employee?.role?.name || 'Sin rol' }}</span><span class="text-[#C7C7CC]">•</span><span :class="user.active ? 'text-[#187A31]' : 'text-[#8E8E93]'">{{ user.active ? 'Activo' : 'Inactivo' }}</span></div>
            </div>
            <div class="flex gap-2">
              <button type="button" class="rounded-lg p-2 text-[#6E6E73] hover:bg-[#F5F5F7] hover:text-[#007AFF]" @click="openEdit(user)" title="Editar">
                <Edit class="size-4" />
              </button>
              <button type="button" :class="['rounded-lg p-2', user.active ? 'text-[#6E6E73] hover:bg-[#F5F5F7] hover:text-[#FF3B30]' : 'text-[#6E6E73] hover:bg-[#F5F5F7] hover:text-[#34C759]']" @click="toggleStatus(user)" :title="user.active ? 'Desactivar' : 'Activar'">
                <X v-if="user.active" class="size-4" />
                <Check v-else class="size-4" />
              </button>
            </div>
          </article>
        </div>

        <div v-else class="flex flex-col items-center px-6 py-14 text-center">
          <div class="flex size-14 items-center justify-center rounded-2xl bg-[#F2F2F7] text-[#8E8E93]"><Users class="size-6" /></div>
          <h2 class="mt-4 text-sm font-semibold">{{ searchQuery ? 'No encontramos coincidencias' : 'Aún no hay usuarios' }}</h2>
          <p class="mt-1 max-w-sm text-xs leading-5 text-[#8E8E93]">{{ searchQuery ? 'Prueba con otro colaborador, usuario o rol.' : 'Selecciona un colaborador de Personal para crear su acceso.' }}</p>
          <button v-if="!searchQuery && availableEmployees.length" type="button" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#007AFF] px-4 py-2 text-sm font-semibold text-white" @click="openCreate"><Plus class="size-4" /> Asignar usuario</button>
        </div>

        <div v-if="users.links?.length > 3" class="flex items-center justify-between border-t px-4 py-3">
          <p class="text-xs text-[#8E8E93]">Mostrando {{ users.from }}–{{ users.to }} de {{ users.total }}</p>
          <div class="flex gap-1"><Link v-for="link in users.links.filter((item) => item.url)" :key="link.label" :href="link.url" :class="['rounded-lg px-2.5 py-1.5 text-xs', link.active ? 'bg-[#007AFF] text-white' : 'text-[#6E6E73]']" v-html="link.label" /></div>
        </div>
      </section>
    </div>

    <div v-if="showCreatePanel" class="fixed inset-0 z-[70] flex justify-end bg-[#1D1D1F]/35 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="closeCreate" @keydown.esc="closeCreate">
      <form class="flex h-full w-full max-w-lg flex-col bg-white shadow-2xl" @submit.prevent="submit">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#E8F2FF] text-[#007AFF]"><UserRound class="size-5" /></div><div><h2 class="text-lg font-bold">Asignar usuario</h2><p class="text-xs text-[#8E8E93]">Vincula el acceso a un colaborador.</p></div></div>
          <button type="button" class="rounded-lg p-2 text-[#8E8E93] hover:bg-[#F5F5F7]" aria-label="Cerrar" @click="closeCreate"><X class="size-4" /></button>
        </header>

        <div class="flex-1 space-y-5 overflow-y-auto p-5">
          <section>
            <div class="mb-2 flex items-center gap-2"><Users class="size-4 text-[#007AFF]" /><h3 class="text-xs font-bold uppercase tracking-wide text-[#6E6E73]">Colaborador</h3></div>
            <select v-model="form.employee_id" autofocus :class="['h-11 w-full rounded-xl border bg-[#F8F8FA] px-3 text-sm focus:ring-2', form.errors.employee_id ? 'border-[#FF3B30]/50 focus:ring-[#FF3B30]/15' : 'border-transparent focus:ring-[#007AFF]/20']">
              <option value="">Selecciona un colaborador</option>
              <option v-for="employee in availableEmployees" :key="employee.id" :value="employee.id">{{ employee.full_name }} — {{ employee.role?.name }}</option>
            </select>
            <p v-if="form.errors.employee_id" class="mt-1 text-xs text-[#D70015]">{{ form.errors.employee_id }}</p>

            <div v-if="selectedEmployee" class="mt-3 flex items-center gap-3 rounded-xl border border-[#D9E8FF] bg-[#F5F9FF] p-3">
              <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#007AFF] text-xs font-bold text-white">{{ initials(selectedEmployee.full_name) }}</div>
              <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ selectedEmployee.full_name }}</p><p class="truncate text-xs text-[#8E8E93]">{{ selectedEmployee.email || selectedEmployee.phone || 'Sin contacto registrado' }}</p></div>
              <span class="inline-flex items-center gap-1 rounded-lg bg-white px-2 py-1 text-xs font-semibold text-[#7A35A8]"><BadgeCheck class="size-3.5" />{{ selectedEmployee.role?.name }}</span>
            </div>
          </section>

          <section class="border-t border-[#EFEFF1] pt-5">
            <div class="mb-3 flex items-center gap-2"><LockKeyhole class="size-4 text-[#AF52DE]" /><h3 class="text-xs font-bold uppercase tracking-wide text-[#6E6E73]">Credenciales</h3></div>
            <div class="space-y-3">
              <div><label for="username" class="mb-1 block text-xs font-semibold">Nombre de usuario</label><input id="username" v-model="form.username" type="text" autocomplete="username" placeholder="Ej. amartinez" :class="['h-10 w-full rounded-xl border bg-[#F8F8FA] px-3 font-mono text-sm lowercase focus:ring-2', form.errors.username ? 'border-[#FF3B30]/50 focus:ring-[#FF3B30]/15' : 'border-transparent focus:ring-[#007AFF]/20']"><p v-if="form.errors.username" class="mt-1 text-xs text-[#D70015]">{{ form.errors.username }}</p></div>

              <div class="grid gap-3 sm:grid-cols-2">
                <div><label for="password" class="mb-1 block text-xs font-semibold">Contraseña</label><div class="relative"><KeyRound class="absolute left-3 top-3 size-4 text-[#8E8E93]" /><input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" placeholder="Mínimo 8 caracteres" class="h-10 w-full rounded-xl border-0 bg-[#F8F8FA] pl-9 pr-10 text-sm focus:ring-2 focus:ring-[#007AFF]/20"><button type="button" class="absolute right-2.5 top-2.5 p-1 text-[#8E8E93]" @click="showPassword = !showPassword"><EyeOff v-if="showPassword" class="size-4" /><Eye v-else class="size-4" /></button></div><p v-if="form.errors.password" class="mt-1 text-xs text-[#D70015]">{{ form.errors.password }}</p></div>
                <div><label for="password-confirmation" class="mb-1 block text-xs font-semibold">Confirmar contraseña</label><input id="password-confirmation" v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" placeholder="Repite la contraseña" class="h-10 w-full rounded-xl border-0 bg-[#F8F8FA] px-3 text-sm focus:ring-2 focus:ring-[#007AFF]/20"></div>
              </div>

              <div><label for="pin" class="mb-1 block text-xs font-semibold">PIN de 4 dígitos</label><div class="relative max-w-48"><LockKeyhole class="absolute left-3 top-3 size-4 text-[#8E8E93]" /><input id="pin" v-model="form.pin" :type="showPin ? 'text' : 'password'" inputmode="numeric" maxlength="4" autocomplete="new-password" placeholder="••••" class="h-10 w-full rounded-xl border-0 bg-[#F8F8FA] pl-9 pr-10 font-mono text-lg tracking-[0.35em] focus:ring-2 focus:ring-[#AF52DE]/20"><button type="button" class="absolute right-2.5 top-2.5 p-1 text-[#8E8E93]" @click="showPin = !showPin"><EyeOff v-if="showPin" class="size-4" /><Eye v-else class="size-4" /></button></div><p v-if="form.errors.pin" class="mt-1 text-xs text-[#D70015]">{{ form.errors.pin }}</p></div>
              <p class="text-[11px] leading-4 text-[#8E8E93]">La contraseña debe incluir letras y números. El PIN se almacena protegido y nunca se muestra en el listado.</p>
            </div>
          </section>

          <section class="flex items-center justify-between gap-4 rounded-xl bg-[#F5F5F7] p-3.5">
            <div><p class="text-sm font-semibold">Usuario activo</p><p class="text-xs text-[#8E8E93]">Podrá ingresar desde su creación.</p></div>
            <button type="button" role="switch" :aria-checked="form.active" :class="['relative h-6 w-11 rounded-full transition', form.active ? 'bg-[#34C759]' : 'bg-[#C7C7CC]']" @click="form.active = !form.active"><span :class="['absolute top-0.5 size-5 rounded-full bg-white shadow', form.active ? 'left-5.5' : 'left-0.5']" /></button>
          </section>
        </div>

        <footer class="flex justify-end gap-2 border-t p-4">
          <button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="closeCreate">Cancelar</button>
          <button type="submit" :disabled="form.processing" class="inline-flex h-10 min-w-36 items-center justify-center gap-2 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white disabled:opacity-50"><Check v-if="!form.processing" class="size-4" />{{ form.processing ? 'Creando...' : 'Crear usuario' }}</button>
        </footer>
      </form>
    </div>

    <div v-if="showEditPanel" class="fixed inset-0 z-[70] flex justify-end bg-[#1D1D1F]/35 backdrop-blur-[2px]" role="dialog" aria-modal="true" @click.self="closeEdit" @keydown.esc="closeEdit">
      <form class="flex h-full w-full max-w-lg flex-col bg-white shadow-2xl" @submit.prevent="submitEdit">
        <header class="flex items-start justify-between border-b px-5 py-4">
          <div class="flex gap-3"><div class="flex size-10 items-center justify-center rounded-xl bg-[#E8F2FF] text-[#007AFF]"><UserRound class="size-5" /></div><div><h2 class="text-lg font-bold">Editar usuario</h2><p class="text-xs text-[#8E8E93]">Modifica las credenciales y estado del usuario.</p></div></div>
          <button type="button" class="rounded-lg p-2 text-[#8E8E93] hover:bg-[#F5F5F7]" aria-label="Cerrar" @click="closeEdit"><X class="size-4" /></button>
        </header>

        <div class="flex-1 space-y-5 overflow-y-auto p-5">
          <section v-if="editingUser">
            <div class="mb-2 flex items-center gap-2"><Users class="size-4 text-[#007AFF]" /><h3 class="text-xs font-bold uppercase tracking-wide text-[#6E6E73]">Colaborador</h3></div>
            <div class="flex items-center gap-3 rounded-xl border border-[#D9E8FF] bg-[#F5F9FF] p-3">
              <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#007AFF] text-xs font-bold text-white">{{ initials(editingUser.name) }}</div>
              <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ editingUser.name }}</p><p class="truncate text-xs text-[#8E8E93]">{{ editingUser.email || editingUser.phone || 'Sin contacto registrado' }}</p></div>
              <span class="inline-flex items-center gap-1 rounded-lg bg-white px-2 py-1 text-xs font-semibold text-[#7A35A8]"><BadgeCheck class="size-3.5" />{{ editingUser.employee?.role?.name }}</span>
            </div>
          </section>

          <section class="border-t border-[#EFEFF1] pt-5">
            <div class="mb-3 flex items-center gap-2"><LockKeyhole class="size-4 text-[#AF52DE]" /><h3 class="text-xs font-bold uppercase tracking-wide text-[#6E6E73]">Credenciales</h3></div>
            <div class="space-y-3">
              <div><label for="edit-username" class="mb-1 block text-xs font-semibold">Nombre de usuario</label><input id="edit-username" v-model="editForm.username" type="text" autocomplete="username" placeholder="Ej. amartinez" :class="['h-10 w-full rounded-xl border bg-[#F8F8FA] px-3 font-mono text-sm lowercase focus:ring-2', editForm.errors.username ? 'border-[#FF3B30]/50 focus:ring-[#FF3B30]/15' : 'border-transparent focus:ring-[#007AFF]/20']"><p v-if="editForm.errors.username" class="mt-1 text-xs text-[#D70015]">{{ editForm.errors.username }}</p></div>

              <div class="grid gap-3 sm:grid-cols-2">
                <div><label for="edit-password" class="mb-1 block text-xs font-semibold">Contraseña (opcional)</label><div class="relative"><KeyRound class="absolute left-3 top-3 size-4 text-[#8E8E93]" /><input id="edit-password" v-model="editForm.password" :type="showEditPassword ? 'text' : 'password'" autocomplete="new-password" placeholder="Dejar vacío para mantener" class="h-10 w-full rounded-xl border-0 bg-[#F8F8FA] pl-9 pr-10 text-sm focus:ring-2 focus:ring-[#007AFF]/20"><button type="button" class="absolute right-2.5 top-2.5 p-1 text-[#8E8E93]" @click="showEditPassword = !showEditPassword"><EyeOff v-if="showEditPassword" class="size-4" /><Eye v-else class="size-4" /></button></div><p v-if="editForm.errors.password" class="mt-1 text-xs text-[#D70015]">{{ editForm.errors.password }}</p></div>
                <div><label for="edit-password-confirmation" class="mb-1 block text-xs font-semibold">Confirmar contraseña</label><input id="edit-password-confirmation" v-model="editForm.password_confirmation" :type="showEditPassword ? 'text' : 'password'" autocomplete="new-password" placeholder="Repite la contraseña" class="h-10 w-full rounded-xl border-0 bg-[#F8F8FA] px-3 text-sm focus:ring-2 focus:ring-[#007AFF]/20"></div>
              </div>

              <div><label for="edit-pin" class="mb-1 block text-xs font-semibold">PIN de 4 dígitos (opcional)</label><div class="relative max-w-48"><LockKeyhole class="absolute left-3 top-3 size-4 text-[#8E8E93]" /><input id="edit-pin" v-model="editForm.pin" :type="showEditPin ? 'text' : 'password'" inputmode="numeric" maxlength="4" autocomplete="new-password" placeholder="••••" class="h-10 w-full rounded-xl border-0 bg-[#F8F8FA] pl-9 pr-10 font-mono text-lg tracking-[0.35em] focus:ring-2 focus:ring-[#AF52DE]/20"><button type="button" class="absolute right-2.5 top-2.5 p-1 text-[#8E8E93]" @click="showEditPin = !showEditPin"><EyeOff v-if="showEditPin" class="size-4" /><Eye v-else class="size-4" /></button></div><p v-if="editForm.errors.pin" class="mt-1 text-xs text-[#D70015]">{{ editForm.errors.pin }}</p></div>
              <p class="text-[11px] leading-4 text-[#8E8E93]">Deja la contraseña y PIN vacíos para mantener los actuales. Si los modificas, la contraseña debe incluir letras y números.</p>
            </div>
          </section>

          <section class="flex items-center justify-between gap-4 rounded-xl bg-[#F5F5F7] p-3.5">
            <div><p class="text-sm font-semibold">Usuario activo</p><p class="text-xs text-[#8E8E93]">Podrá ingresar al sistema.</p></div>
            <button type="button" role="switch" :aria-checked="editForm.active" :class="['relative h-6 w-11 rounded-full transition', editForm.active ? 'bg-[#34C759]' : 'bg-[#C7C7CC]']" @click="editForm.active = !editForm.active"><span :class="['absolute top-0.5 size-5 rounded-full bg-white shadow', editForm.active ? 'left-5.5' : 'left-0.5']" /></button>
          </section>
        </div>

        <footer class="flex justify-end gap-2 border-t p-4">
          <button type="button" class="h-10 rounded-xl border px-4 text-sm font-semibold" @click="closeEdit">Cancelar</button>
          <button type="submit" :disabled="editForm.processing" class="inline-flex h-10 min-w-36 items-center justify-center gap-2 rounded-xl bg-[#007AFF] px-4 text-sm font-semibold text-white disabled:opacity-50"><Check v-if="!editForm.processing" class="size-4" />{{ editForm.processing ? 'Guardando...' : 'Guardar cambios' }}</button>
        </footer>
      </form>
    </div>
  </AppShell>
</template>
