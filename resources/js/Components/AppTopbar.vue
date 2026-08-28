<script setup>
import { ref } from 'vue'
import { Search, Bell, HelpCircle, LogOut, Menu } from '@lucide/vue'

const searchQuery = ref('')
const showMobileMenu = ref(false)

const emit = defineEmits(['toggle-mobile-menu'])
</script>

<template>
  <header class="bg-white border-b border-gray-200 h-16 fixed right-0 left-64 top-0 z-10">
    <div class="flex items-center justify-between h-full px-6">
      <div class="flex items-center flex-1">
        <button 
          @click="emit('toggle-mobile-menu')"
          class="lg:hidden mr-4 p-2 rounded-md hover:bg-gray-100"
        >
          <Menu class="w-5 h-5" />
        </button>

        <div class="relative max-w-md w-full">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar productores, rutas, planillas..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2D5A3D] focus:border-transparent text-sm"
          />
        </div>
      </div>

      <div class="flex items-center space-x-4">
        <button class="p-2 rounded-full hover:bg-gray-100 relative">
          <Bell class="w-5 h-5 text-gray-600" />
          <span class="absolute top-1 right-1 w-2 h-2 bg-[#D97706] rounded-full"></span>
        </button>

        <button class="p-2 rounded-full hover:bg-gray-100">
          <HelpCircle class="w-5 h-5 text-gray-600" />
        </button>

        <div class="h-6 w-px bg-gray-200"></div>

        <button class="flex items-center space-x-2 hover:bg-gray-100 px-3 py-2 rounded-lg transition-colors">
          <div class="w-8 h-8 bg-[#1E3A5F] rounded-full flex items-center justify-center text-white text-sm font-medium">
            {{ $page.props.auth?.user?.name?.charAt(0) }}
          </div>
          <div class="hidden md:block text-left">
            <p class="text-sm font-medium text-gray-900">{{ $page.props.auth?.user?.name }}</p>
            <p class="text-xs text-gray-500">{{ $page.props.auth?.companies?.[0]?.name }}</p>
          </div>
        </button>

        <button class="p-2 rounded-full hover:bg-gray-100 text-gray-600">
          <LogOut class="w-5 h-5" />
        </button>
      </div>
    </div>
  </header>
</template>
