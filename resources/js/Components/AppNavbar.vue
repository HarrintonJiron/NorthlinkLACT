<script setup>
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Search, Bell, HelpCircle, Menu, ChevronDown, LogOut } from '@lucide/vue'
import { clearPrivateOfflineData } from '@/offline/sumniOffline'

const props = defineProps({
  currentRoute: String
})

const searchQuery = ref('')
const mobileMenuOpen = ref(false)
const activeDropdown = ref(null)
const page = usePage()
const allowedModules = computed(() => page.props.auth?.modules || [])
const fieldProducerNotifications = computed(() => page.props.notifications?.field_producers || { count: 0, items: [] })
const correctionRequestNotifications = computed(() => page.props.notifications?.correction_requests || { count: 0, items: [] })
const notificationCount = computed(() => fieldProducerNotifications.value.count + correctionRequestNotifications.value.count)
const canAccess = (module) => !module || allowedModules.value.includes('*') || allowedModules.value.includes(module)

const navigation = computed(() => [
  { name: 'Dashboard', href: '/' },
  {
    name: 'Acopio',
    children: [
      { name: 'Sumni', href: '/sumni', module: 'sumni' },
      { name: 'Rutas', href: '/routes', module: 'routes' },
      { name: 'Rutero', href: '/ruteros', module: 'ruteros' },
      { name: 'Recolecciones', href: '/collections', module: 'collections' },
      { name: 'Productores', href: '/producers', module: 'producers' },
    ]
  },
  {
    name: 'Finanzas',
    children: [
      { name: 'Movimientos', href: '/finanzas', module: 'finances' },
      { name: 'Planillas de pago', href: '/payment-sheets', module: 'finances' },
      { name: 'Pagos', href: '/payments', module: 'finances' },
    ]
  },
  {
    name: 'Operaciones',
    children: [
      { name: 'Producción', href: '/production', module: 'production' },
      { name: 'Inventario', href: '/inventory', module: 'inventory' },
    ]
  },
  {
    name: 'Recursos',
    children: [
      { name: 'Personal', href: '/employees', module: 'personnel' },
      { name: 'Nómina', href: '/payroll', module: 'payroll' },
    ]
  },
  {
    name: 'Sistema',
    children: [
      { name: 'Reportes', href: '/reports', module: 'reports' },
      { name: 'Configuración', href: '/settings', module: 'administrator' },
    ]
  }
].map((item) => item.children
  ? { ...item, children: item.children.filter((child) => child.module === 'administrator' ? allowedModules.value.includes('*') : canAccess(child.module)) }
  : item)
  .filter((item) => !item.children || item.children.length))

const matchesPath = (href) => {
  if (href === '/') {
    return props.currentRoute === '/'
  }

  return props.currentRoute === href || props.currentRoute?.startsWith(`${href}/`)
}

const isSectionCurrent = (children) => children?.some(child => matchesPath(child.href))

const toggleDropdown = (name) => {
  activeDropdown.value = activeDropdown.value === name ? null : name
}

const closeDropdown = () => {
  activeDropdown.value = null
}

const closeMobileMenu = () => {
  mobileMenuOpen.value = false
}

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

const logout = async () => {
  await clearPrivateOfflineData().catch(() => {})
  router.post('/logout')
}
</script>

<template>
  <header class="bg-white/80 backdrop-blur-xl border-b border-[#E5E5E5] sticky top-0 z-40 pl-56">
    <div class="max-w-full mx-auto px-6">
      <div class="flex items-center justify-between h-16">
        <nav class="hidden lg:flex items-center space-x-1">
          <div
            v-for="item in navigation"
            :key="item.name"
            class="relative"
          >
            <Link
              v-if="!item.children"
              :href="item.href"
              :class="[
                'flex items-center px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200',
                matchesPath(item.href)
                  ? 'text-[#007AFF] bg-[#E5F1FF]'
                  : 'text-[#6E6E73] hover:text-[#1D1D1F] hover:bg-[#F5F5F7]'
              ]"
            >
              {{ item.name }}
            </Link>

            <button
              v-else
              type="button"
              :class="[
                'flex items-center px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200',
                activeDropdown === item.name || isSectionCurrent(item.children)
                  ? 'text-[#007AFF] bg-[#E5F1FF]'
                  : 'text-[#6E6E73] hover:text-[#1D1D1F] hover:bg-[#F5F5F7]'
              ]"
              :aria-expanded="activeDropdown === item.name"
              @click="toggleDropdown(item.name)"
            >
              {{ item.name }}
              <ChevronDown class="w-4 h-4 ml-1" />
            </button>

            <div
              v-if="item.children && activeDropdown === item.name"
              class="absolute top-full left-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-[#E5E5E5] py-2"
            >
              <Link
                v-for="child in item.children"
                :key="child.name"
                :href="child.href"
                :class="[
                  'block px-4 py-2 text-sm transition-colors',
                  matchesPath(child.href)
                    ? 'text-[#007AFF] bg-[#F5F5F7]'
                    : 'text-[#6E6E73] hover:text-[#007AFF] hover:bg-[#F5F5F7]'
                ]"
                @click="closeDropdown"
              >
                {{ child.name }}
              </Link>
            </div>
          </div>
        </nav>

        <div class="flex items-center space-x-2">
          <div class="hidden md:block relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar..."
              class="pl-10 pr-4 py-2 bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-sm w-48 text-[#1D1D1F] placeholder-[#8E8E93] transition-all"
            />
          </div>

          <div class="relative hidden sm:block">
            <button
              type="button"
              class="relative rounded-xl p-2.5 transition-colors hover:bg-[#F5F5F7]"
              :aria-expanded="activeDropdown === 'notifications'"
              aria-label="Notificaciones"
              @click="toggleDropdown('notifications')"
            >
              <Bell class="h-5 w-5 text-[#8E8E93]" />
              <span
                v-if="notificationCount"
                class="absolute -right-1 -top-1 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-[#FF3B30] px-1 text-[10px] font-bold text-white"
              >
                {{ notificationCount > 99 ? '99+' : notificationCount }}
              </span>
            </button>

            <div
              v-if="activeDropdown === 'notifications'"
              class="absolute right-0 top-full mt-2 w-96 overflow-hidden rounded-2xl border border-[#E5E5E5] bg-white shadow-xl"
            >
              <div class="border-b border-[#E5E5E5] px-4 py-3">
                <p class="font-semibold text-[#1D1D1F]">Notificaciones</p>
                <p class="text-xs text-[#6E6E73]">Incidencias de campo y altas rápidas</p>
              </div>

              <div v-if="correctionRequestNotifications.items.length" class="max-h-96 overflow-y-auto py-1">
                <Link
                  v-for="notification in correctionRequestNotifications.items"
                  :key="`correction-${notification.id}`"
                  :href="notification.href"
                  class="block border-b border-[#F5F5F7] bg-red-50/60 px-4 py-3 transition-colors hover:bg-red-50"
                  @click="closeDropdown"
                >
                  <div class="flex items-start gap-3">
                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-[#FF3B30]"></span>
                    <div class="min-w-0">
                      <p class="text-sm font-semibold text-[#1D1D1F]">Corrección solicitada: {{ notification.producer }}</p>
                      <p class="mt-0.5 text-xs text-[#6E6E73]">{{ notification.operator || 'Rutero' }} · {{ notification.route || 'Sin ruta' }}</p>
                      <p class="mt-1 line-clamp-2 text-xs font-medium text-red-700">{{ notification.reason }}</p>
                    </div>
                  </div>
                </Link>
              </div>

              <div v-if="fieldProducerNotifications.items.length" class="max-h-96 overflow-y-auto py-1">
                <Link
                  v-for="notification in fieldProducerNotifications.items"
                  :key="notification.id"
                  :href="notification.href"
                  class="block border-b border-[#F5F5F7] px-4 py-3 transition-colors last:border-0 hover:bg-[#F5F5F7]"
                  @click="closeDropdown"
                >
                  <div class="flex items-start gap-3">
                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-[#FF9500]"></span>
                    <div class="min-w-0">
                      <p class="text-sm font-semibold text-[#1D1D1F]">Nuevo cliente: {{ notification.name }}</p>
                      <p class="mt-0.5 text-xs text-[#6E6E73]">
                        {{ notification.phone || 'Sin teléfono' }} · {{ notification.route || 'Sin ruta' }}
                      </p>
                      <p class="mt-1 text-xs font-medium text-[#007AFF]">Completar expediente del productor</p>
                    </div>
                  </div>
                </Link>
              </div>

              <p v-else class="px-4 py-6 text-center text-sm text-[#6E6E73]">
                No hay altas rápidas pendientes.
              </p>

              <Link
                v-if="fieldProducerNotifications.count > fieldProducerNotifications.items.length"
                href="/producers"
                class="block border-t border-[#E5E5E5] px-4 py-3 text-center text-sm font-medium text-[#007AFF] hover:bg-[#F5F5F7]"
                @click="closeDropdown"
              >
                Ver todos los productores
              </Link>
            </div>
          </div>

          <button type="button" class="hidden sm:block p-2.5 rounded-xl hover:bg-[#F5F5F7] transition-colors">
            <HelpCircle class="w-5 h-5 text-[#8E8E93]" />
          </button>

          <div class="flex items-center space-x-3 px-3 py-2 rounded-xl hover:bg-[#F5F5F7] transition-colors">
            <div class="w-8 h-8 bg-gradient-to-br from-[#007AFF] to-[#5AC8FA] rounded-full flex items-center justify-center text-white text-sm font-semibold shadow-sm">
              {{ $page.props.auth?.user?.name?.charAt(0) || 'U' }}
            </div>
            <div class="hidden md:block text-left">
              <p class="text-sm font-medium text-[#1D1D1F]">{{ $page.props.auth?.user?.name || 'Usuario' }}</p>
            </div>
          </div>

          <button
            type="button"
            class="rounded-xl p-2.5 text-[#8E8E93] transition-colors hover:bg-[#F5F5F7] hover:text-[#FF3B30]"
            aria-label="Cerrar sesión"
            title="Cerrar sesión"
            @click="logout"
          >
            <LogOut class="size-5" />
          </button>

          <button
            type="button"
            class="lg:hidden p-2 rounded-xl hover:bg-[#F5F5F7] transition-colors"
            :aria-expanded="mobileMenuOpen"
            aria-label="Abrir navegación"
            @click="toggleMobileMenu"
          >
            <Menu class="w-5 h-5 text-[#1D1D1F]" />
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="mobileMenuOpen"
      class="lg:hidden border-t border-[#E5E5E5] bg-white"
    >
      <nav class="px-4 py-4 space-y-3">
        <template v-for="item in navigation" :key="item.name">
          <Link
            v-if="!item.children"
            :href="item.href"
            :class="[
              'flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-colors',
              matchesPath(item.href)
                ? 'text-[#007AFF] bg-[#E5F1FF]'
                : 'text-[#6E6E73] hover:text-[#007AFF] hover:bg-[#F5F5F7]'
            ]"
            @click="closeMobileMenu"
          >
            {{ item.name }}
          </Link>

          <div v-else>
            <p class="px-4 pb-1 text-xs font-semibold uppercase tracking-wide text-[#8E8E93]">
              {{ item.name }}
            </p>
            <Link
              v-for="child in item.children"
              :key="child.name"
              :href="child.href"
              :class="[
                'flex items-center px-4 py-2 text-sm font-medium rounded-xl transition-colors',
                matchesPath(child.href)
                  ? 'text-[#007AFF] bg-[#E5F1FF]'
                  : 'text-[#6E6E73] hover:text-[#007AFF] hover:bg-[#F5F5F7]'
              ]"
              @click="closeMobileMenu"
            >
              {{ child.name }}
            </Link>
          </div>
        </template>
      </nav>
    </div>
  </header>
</template>
