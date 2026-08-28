<script setup>
import { ref } from 'vue'
import { Search, Bell, HelpCircle, Menu, MapPin, Users, FileText, Wallet, Factory, UserCheck, Calculator, BarChart3, Settings, ChevronDown } from '@lucide/vue'

const props = defineProps({
  currentRoute: String
})

const searchQuery = ref('')
const mobileMenuOpen = ref(false)
const activeDropdown = ref(null)

const navigation = [
  { name: 'Dashboard', href: '/', icon: null, current: props.currentRoute === '/' },
  { 
    name: 'Acopio', 
    icon: null,
    children: [
      { name: 'Rutas', href: '/routes' },
      { name: 'Productores', href: '/producers' },
    ]
  },
  { 
    name: 'Finanzas', 
    icon: null,
    children: [
      { name: 'Planillas', href: '/payment-sheets' },
      { name: 'Pagos', href: '/payments' },
    ]
  },
  { 
    name: 'Operaciones', 
    icon: null,
    children: [
      { name: 'Producción', href: '/production' },
      { name: 'Inventario', href: '/inventory' },
    ]
  },
  { 
    name: 'Recursos', 
    icon: null,
    children: [
      { name: 'Personal', href: '/employees' },
      { name: 'Nómina', href: '/payroll' },
    ]
  },
  { 
    name: 'Sistema', 
    icon: null,
    children: [
      { name: 'Reportes', href: '/reports' },
      { name: 'Configuración', href: '/settings' },
    ]
  }
]

const toggleDropdown = (name) => {
  if (activeDropdown.value === name) {
    activeDropdown.value = null
  } else {
    activeDropdown.value = name
  }
}

const closeDropdown = () => {
  activeDropdown.value = null
}

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}
</script>

<template>
  <header class="bg-white/80 backdrop-blur-xl border-b border-[#E5E5E5] sticky top-0 z-40 pl-56">
    <div class="max-w-full mx-auto px-6">
      <div class="flex items-center justify-between h-16">
        <!-- Navigation -->
        <nav class="hidden lg:flex items-center space-x-1">
          <a
            v-for="item in navigation"
            :key="item.name"
            @click="item.children ? toggleDropdown(item.name) : null"
            class="relative group"
          >
            <div
              v-if="!item.children"
              :href="item.href"
              :class="[
                'flex items-center px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200',
                item.current 
                  ? 'text-[#007AFF] bg-[#E5F1FF]' 
                  : 'text-[#6E6E73] hover:text-[#1D1D1F] hover:bg-[#F5F5F7]'
              ]"
            >
              {{ item.name }}
            </div>
            <button
              v-else
              :class="[
                'flex items-center px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200',
                activeDropdown === item.name 
                  ? 'text-[#007AFF] bg-[#E5F1FF]' 
                  : 'text-[#6E6E73] hover:text-[#1D1D1F] hover:bg-[#F5F5F7]'
              ]"
            >
              {{ item.name }}
              <ChevronDown class="w-4 h-4 ml-1" />
            </button>

            <!-- Dropdown -->
            <div
              v-if="item.children && activeDropdown === item.name"
              class="absolute top-full left-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-[#E5E5E5] py-2"
              @click.stop
            >
              <a
                v-for="child in item.children"
                :key="child.name"
                :href="child.href"
                class="block px-4 py-2 text-sm text-[#6E6E73] hover:text-[#007AFF] hover:bg-[#F5F5F7] transition-colors"
                @click="closeDropdown"
              >
                {{ child.name }}
              </a>
            </div>
          </a>
        </nav>

        <!-- Right side -->
        <div class="flex items-center space-x-2">
          <!-- Search -->
          <div class="hidden md:block relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar..."
              class="pl-10 pr-4 py-2 bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-sm w-48 text-[#1D1D1F] placeholder-[#8E8E93] transition-all"
            />
          </div>

          <!-- Notifications -->
          <button class="hidden sm:block p-2.5 rounded-xl hover:bg-[#F5F5F7] transition-colors relative">
            <Bell class="w-5 h-5 text-[#8E8E93]" />
            <span class="absolute top-1 right-1 w-2 h-2 bg-[#FF3B30] rounded-full"></span>
          </button>

          <!-- Help -->
          <button class="hidden sm:block p-2.5 rounded-xl hover:bg-[#F5F5F7] transition-colors">
            <HelpCircle class="w-5 h-5 text-[#8E8E93]" />
          </button>

          <!-- Profile -->
          <div class="flex items-center space-x-3 px-3 py-2 rounded-xl hover:bg-[#F5F5F7] transition-colors cursor-pointer">
            <div class="w-8 h-8 bg-gradient-to-br from-[#007AFF] to-[#5AC8FA] rounded-full flex items-center justify-center text-white text-sm font-semibold shadow-sm">
              {{ $page.props.auth?.user?.name?.charAt(0) || 'U' }}
            </div>
            <div class="hidden md:block text-left">
              <p class="text-sm font-medium text-[#1D1D1F]">{{ $page.props.auth?.user?.name || 'Usuario' }}</p>
            </div>
          </div>

          <!-- Mobile menu button -->
          <button 
            @click="toggleMobileMenu"
            class="lg:hidden p-2 rounded-xl hover:bg-[#F5F5F7] transition-colors"
          >
            <Menu class="w-5 h-5 text-[#1D1D1F]" />
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div 
      v-if="mobileMenuOpen"
      class="lg:hidden border-t border-[#E5E5E5] bg-white"
    >
      <nav class="px-4 py-4 space-y-1">
        <a
          v-for="item in navigation"
          :key="item.name"
          :href="item.href || '#'"
          class="flex items-center px-4 py-3 text-sm font-medium rounded-xl text-[#6E6E73] hover:text-[#007AFF] hover:bg-[#F5F5F7] transition-colors"
          @click="toggleMobileMenu"
        >
          {{ item.name }}
        </a>
      </nav>
    </div>
  </header>
</template>
