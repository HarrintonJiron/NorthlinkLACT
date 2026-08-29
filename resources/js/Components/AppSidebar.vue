<script setup>
import { ref } from 'vue'
import { 
  LayoutDashboard, 
  Droplets,
  MapPin,
  Truck,
  Users, 
  FileText, 
  Wallet, 
  Package, 
  Warehouse, 
  UserCheck, 
  Calculator, 
  BarChart3, 
  Settings,
  ChevronDown,
  ChevronRight
} from '@lucide/vue'

const props = defineProps({
  currentRoute: String
})

const expandedSections = ref(['acopio'])

const navigation = [
  {
    section: 'principal',
    items: [
      { name: 'Inicio', href: '/', icon: LayoutDashboard, current: props.currentRoute === '/' },
    ]
  },
  {
    section: 'acopio',
    title: 'Acopio',
    items: [
      { name: 'Sumni', href: '/sumni', icon: Droplets, current: props.currentRoute?.includes('sumni') },
      { name: 'Rutas', href: '/routes', icon: MapPin, current: props.currentRoute?.includes('routes') },
      { name: 'Rutero', href: '/ruteros', icon: Truck, current: props.currentRoute?.includes('ruteros') },
      { name: 'Productores', href: '/producers', icon: Users, current: props.currentRoute?.includes('producers') },
    ]
  },
  {
    section: 'finanzas',
    title: 'Finanzas',
    items: [
      { name: 'Planillas', href: '/payment-sheets', icon: FileText },
      { name: 'Pagos', href: '/payments', icon: Wallet },
    ]
  },
  {
    section: 'operaciones',
    title: 'Operaciones',
    items: [
      { name: 'Producción', href: '/production', icon: Package },
      { name: 'Inventario', href: '/inventory', icon: Warehouse },
    ]
  },
  {
    section: 'recursos',
    title: 'Recursos',
    items: [
      { name: 'Personal', href: '/employees', icon: UserCheck },
      { name: 'Nómina', href: '/payroll', icon: Calculator },
    ]
  },
  {
    section: 'sistema',
    title: 'Sistema',
    items: [
      { name: 'Reportes', href: '/reports', icon: BarChart3 },
      { name: 'Configuración', href: '/settings', icon: Settings },
    ]
  }
]

const toggleSection = (section) => {
  const index = expandedSections.value.indexOf(section)
  if (index > -1) {
    expandedSections.value.splice(index, 1)
  } else {
    expandedSections.value.push(section)
  }
}

const isExpanded = (section) => {
  return expandedSections.value.includes(section)
}
</script>

<template>
  <aside class="w-64 bg-[#1D1D1F] h-screen fixed left-0 top-0 flex flex-col">
    <div class="p-6">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-gradient-to-br from-[#007AFF] to-[#5AC8FA] rounded-xl flex items-center justify-center shadow-lg">
          <Package class="w-6 h-6 text-white" />
        </div>
        <div>
          <h1 class="font-display font-bold text-lg text-white">Northlink</h1>
          <p class="text-xs text-[#8E8E93]">LACT</p>
        </div>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3">
      <div v-for="group in navigation" :key="group.section" class="mb-1">
        <button 
          v-if="group.title"
          @click="toggleSection(group.section)"
          class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium text-[#8E8E93] hover:text-white hover:bg-[#2C2C2E] rounded-lg transition-all duration-200"
        >
          <span>{{ group.title }}</span>
          <ChevronDown 
            v-if="isExpanded(group.section)" 
            class="w-4 h-4 transition-transform duration-200"
          />
          <ChevronRight 
            v-else 
            class="w-4 h-4 transition-transform duration-200"
          />
        </button>

        <div 
          v-show="!group.title || isExpanded(group.section)"
          class="mt-1 space-y-0.5"
        >
          <a
            v-for="item in group.items"
            :key="item.name"
            :href="item.href"
            :class="[
              'flex items-center px-3 py-2.5 text-sm rounded-lg transition-all duration-200',
              item.current 
                ? 'bg-[#007AFF] text-white shadow-md' 
                : 'text-[#8E8E93] hover:text-white hover:bg-[#2C2C2E]'
            ]"
          >
            <component :is="item.icon" class="w-5 h-5 mr-3" />
            {{ item.name }}
          </a>
        </div>
      </div>
    </nav>

    <div class="p-4 border-t border-[#2C2C2E]">
      <div class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-[#2C2C2E] transition-colors cursor-pointer">
        <div class="w-9 h-9 bg-gradient-to-br from-[#34C759] to-[#30D158] rounded-full flex items-center justify-center text-white text-sm font-semibold shadow-md">
          {{ $page.props.auth?.user?.name?.charAt(0) || 'U' }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-white truncate">{{ $page.props.auth?.user?.name || 'Usuario' }}</p>
          <p class="text-xs text-[#8E8E93] truncate">{{ $page.props.auth?.companies?.[0]?.name || 'Empresa' }}</p>
        </div>
      </div>
    </div>
  </aside>
</template>
