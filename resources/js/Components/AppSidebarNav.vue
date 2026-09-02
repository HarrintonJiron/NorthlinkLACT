<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Package, Droplets, MapPin, Truck, Users, Wallet, Factory, UserCheck, Calculator, BarChart3, Settings } from '@lucide/vue'

const props = defineProps({
  currentRoute: String
})

const page = usePage()
const allowedModules = computed(() => page.props.auth?.modules || [])

const navigation = computed(() => [
  { name: 'Dashboard', href: '/', icon: Package },
  { name: 'Sumni', href: '/sumni', icon: Droplets, module: 'sumni' },
  { name: 'Rutas', href: '/routes', icon: MapPin, module: 'routes' },
  { name: 'Rutero', href: '/ruteros', icon: Truck, module: 'ruteros' },
  { name: 'Recolecciones', href: '/collections', icon: Droplets, module: 'collections' },
  { name: 'Productores', href: '/producers', icon: Users, module: 'producers' },
  { name: 'Finanzas', href: '/finanzas', icon: Wallet, module: 'finances', activePrefixes: ['/finanzas', '/payment'] },
  { name: 'Producción', href: '/production', icon: Factory, module: 'production' },
  { name: 'Inventario', href: '/inventory', icon: Factory, module: 'inventory' },
  { name: 'Personal', href: '/employees', icon: UserCheck, module: 'personnel' },
  { name: 'Nómina', href: '/payroll', icon: Calculator, module: 'payroll' },
  { name: 'Reportes', href: '/reports', icon: BarChart3, module: 'reports' },
  { name: 'Configuración', href: '/settings', icon: Settings, module: 'administrator' },
].filter((item) => !item.module
  || (item.module === 'administrator' ? allowedModules.value.includes('*') : allowedModules.value.includes('*') || allowedModules.value.includes(item.module))))

const matchesPath = (href) => {
  if (href === '/') {
    return props.currentRoute === '/'
  }

  return props.currentRoute === href || props.currentRoute?.startsWith(`${href}/`)
}

const isCurrent = (item) => {
  return (item.activePrefixes ?? [item.href]).some(matchesPath)
}
</script>

<template>
  <aside class="w-56 bg-white border-r border-[#E5E5E5] fixed left-0 top-0 bottom-0 flex flex-col z-50">
    <div class="p-4 border-b border-[#E5E5E5]">
      <div class="flex items-center space-x-3">
        <div class="w-9 h-9 bg-gradient-to-br from-[#007AFF] to-[#5AC8FA] rounded-xl flex items-center justify-center shadow-md">
          <Package class="w-5 h-5 text-white" />
        </div>
        <div>
          <h1 class="font-display font-bold text-lg text-[#1D1D1F]">Northlink</h1>
          <p class="text-xs text-[#8E8E93]">LACT</p>
        </div>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 space-y-1 px-3">
      <Link
        v-for="item in navigation"
        :key="item.name"
        :href="item.href"
        :class="[
          'flex items-center px-3 py-2.5 rounded-xl transition-all duration-200',
          isCurrent(item)
            ? 'bg-[#007AFF] text-white shadow-md'
            : 'text-[#6E6E73] hover:text-[#007AFF] hover:bg-[#F5F5F7]'
        ]"
      >
        <component :is="item.icon" class="w-5 h-5 mr-3" />
        <span class="text-sm font-medium">{{ item.name }}</span>
      </Link>
    </nav>
  </aside>
</template>
