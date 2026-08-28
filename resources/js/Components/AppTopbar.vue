<script setup>
import { ref } from 'vue'
import { Search, Bell, HelpCircle, LogOut, Menu } from '@lucide/vue'

const searchQuery = ref('')
const showMobileMenu = ref(false)

const emit = defineEmits(['toggle-mobile-menu'])
</script>

<template>
  <header class="bg-white/80 backdrop-blur-xl border-b border-[#E5E5E5] h-16 fixed right-0 left-64 top-0 z-10">
    <div class="flex items-center justify-between h-full px-6">
      <div class="flex items-center flex-1">
        <button 
          @click="emit('toggle-mobile-menu')"
          class="lg:hidden mr-4 p-2 rounded-lg hover:bg-[#F5F5F7] transition-colors"
        >
          <Menu class="w-5 h-5 text-[#1D1D1F]" />
        </button>

        <div class="relative max-w-md w-full">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-[#8E8E93]" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar..."
            class="w-full pl-10 pr-4 py-2 bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 text-sm text-[#1D1D1F] placeholder-[#8E8E93] transition-all"
          />
        </div>
      </div>

      <div class="flex items-center space-x-2">
        <button class="p-2.5 rounded-xl hover:bg-[#F5F5F7] transition-colors relative">
          <Bell class="w-5 h-5 text-[#8E8E93]" />
          <span class="absolute top-1 right-1 w-2 h-2 bg-[#FF3B30] rounded-full"></span>
        </button>

        <button class="p-2.5 rounded-xl hover:bg-[#F5F5F7] transition-colors">
          <HelpCircle class="w-5 h-5 text-[#8E8E93]" />
        </button>

        <div class="h-6 w-px bg-[#E5E5E5] mx-2"></div>

        <button class="flex items-center space-x-3 hover:bg-[#F5F5F7] px-3 py-2 rounded-xl transition-colors">
          <div class="w-9 h-9 bg-gradient-to-br from-[#007AFF] to-[#5AC8FA] rounded-full flex items-center justify-center text-white text-sm font-semibold shadow-sm">
            {{ $page.props.auth?.user?.name?.charAt(0) || 'U' }}
          </div>
          <div class="hidden md:block text-left">
            <p class="text-sm font-medium text-[#1D1D1F]">{{ $page.props.auth?.user?.name || 'Usuario' }}</p>
          </div>
        </button>
      </div>
    </div>
  </header>
</template>
