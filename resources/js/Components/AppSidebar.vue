<script setup>
import { ref } from 'vue'
import { 
  LayoutDashboard, 
  MapPin, 
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
      { name: 'Rutas', href: '/routes', icon: MapPin, current: props.currentRoute?.includes('routes') },
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
  <aside class="w-64 bg-[#1E3A5F] text-white h-screen fixed left-0 top-0 flex flex-col">
    <div class="p-6 border-b border-[#2D4A6F]">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-[#2D5A3D] rounded-lg flex items-center justify-center">
          <Package class="w-6 h-6" />
        </div>
        <div>
          <h1 class="font-display font-bold text-lg">Northlink</h1>
          <p class="text-xs text-gray-300">LACT</p>
        </div>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4">
      <div v-for="group in navigation" :key="group.section" class="px-3 mb-2">
        <button 
          v-if="group.title"
          @click="toggleSection(group.section)"
          class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-[#2D4A6F] rounded-md transition-colors"
        >
          <span>{{ group.title }}</span>
          <ChevronDown 
            v-if="isExpanded(group.section)" 
            class="w-4 h-4 transition-transform"
          />
          <ChevronRight 
            v-else 
            class="w-4 h-4 transition-transform"
          />
        </button>

        <div 
          v-show="!group.title || isExpanded(group.section)"
          class="mt-1 space-y-1"
        >
          <a
            v-for="item in group.items"
            :key="item.name"
            :href="item.href"
            :class="[
              'flex items-center px-3 py-2 text-sm rounded-md transition-colors',
              item.current 
                ? 'bg-[#2D5A3D] text-white' 
                : 'text-gray-300 hover:text-white hover:bg-[#2D4A6F]'
            ]"
          >
            <component :is="item.icon" class="w-5 h-5 mr-3" />
            {{ item.name }}
          </a>
        </div>
      </div>
    </nav>

    <div class="p-4 border-t border-[#2D4A6F]">
      <div class="flex items-center space-x-3">
        <div class="w-8 h-8 bg-[#2D4A6F] rounded-full flex items-center justify-center">
          <UserCheck class="w-4 h-4" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium truncate">{{ $page.props.auth?.user?.name }}</p>
          <p class="text-xs text-gray-400 truncate">{{ $page.props.auth?.companies?.[0]?.name }}</p>
        </div>
      </div>
    </div>
  </aside>
</template>
