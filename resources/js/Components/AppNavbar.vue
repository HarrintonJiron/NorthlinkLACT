<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Search, Bell, HelpCircle, Menu, ChevronDown, LogOut } from '@lucide/vue'

const props = defineProps({
  currentRoute: String
})

const searchQuery = ref('')
const mobileMenuOpen = ref(false)
const activeDropdown = ref(null)
const page = usePage()
const allowedModules = computed(() => page.props.auth?.modules || [])
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

          <button type="button" class="hidden sm:block p-2.5 rounded-xl hover:bg-[#F5F5F7] transition-colors relative">
            <Bell class="w-5 h-5 text-[#8E8E93]" />
            <span class="absolute top-1 right-1 w-2 h-2 bg-[#FF3B30] rounded-full"></span>
          </button>

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

          <Link
            href="/logout"
            method="post"
            as="button"
            class="rounded-xl p-2.5 text-[#8E8E93] transition-colors hover:bg-[#F5F5F7] hover:text-[#FF3B30]"
            aria-label="Cerrar sesión"
            title="Cerrar sesión"
          >
            <LogOut class="size-5" />
          </Link>

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
