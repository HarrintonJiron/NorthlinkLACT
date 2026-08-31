<script setup>
import { computed } from 'vue'
import { Package, Droplets, MapPin, Truck, Users, FileText, Wallet, Factory, UserCheck, Calculator, BarChart3, Settings } from '@lucide/vue'

const props = defineProps({
  currentRoute: String
})

const navigation = [
  { name: 'Dashboard', href: '/', icon: Package, current: props.currentRoute === '/' },
  { name: 'Sumni', href: '/sumni', icon: Droplets, current: props.currentRoute?.includes('sumni') },
  { name: 'Rutas', href: '/routes', icon: MapPin, current: props.currentRoute?.includes('routes') },
  { name: 'Rutero', href: '/ruteros', icon: Truck, current: props.currentRoute?.includes('ruteros') },
  { name: 'Productores', href: '/producers', icon: Users, current: props.currentRoute?.includes('producers') },
  { name: 'Finanzas', href: '/finanzas', icon: Wallet, current: props.currentRoute?.includes('finanzas') || props.currentRoute?.includes('payment') },
  { name: 'Producción', href: '/production', icon: Factory },
  { name: 'Inventario', href: '/inventory', icon: Factory },
  { name: 'Personal', href: '/employees', icon: UserCheck, current: props.currentRoute?.includes('employees') },
  { name: 'Nómina', href: '/payroll', icon: Calculator },
  { name: 'Reportes', href: '/reports', icon: BarChart3 },
  { name: 'Configuración', href: '/settings', icon: Settings, current: props.currentRoute?.includes('settings') },
]
</script>

<template>
  <aside class="w-56 bg-white border-r border-[#E5E5E5] fixed left-0 top-0 bottom-0 flex flex-col z-50">
    <!-- Logo -->
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

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 space-y-1 px-3">
      <a
        v-for="item in navigation"
        :key="item.name"
        :href="item.href"
        :class="[
          'flex items-center px-3 py-2.5 rounded-xl transition-all duration-200',
          item.current 
            ? 'bg-[#007AFF] text-white shadow-md' 
            : 'text-[#6E6E73] hover:text-[#007AFF] hover:bg-[#F5F5F7]'
        ]"
      >
        <component :is="item.icon" class="w-5 h-5 mr-3" />
        <span class="text-sm font-medium">{{ item.name }}</span>
      </a>
    </nav>
  </aside>
</template>
