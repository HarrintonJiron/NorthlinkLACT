<script setup>
import { ref, computed } from 'vue'
import AppSidebar from './AppSidebar.vue'
import AppTopbar from './AppTopbar.vue'

const props = defineProps({
  title: String
})

const mobileMenuOpen = ref(false)

const currentRoute = computed(() => {
  return window.location.pathname
})

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}
</script>

<template>
  <div class="min-h-screen bg-[#FAFAF8]">
    <AppSidebar :current-route="currentRoute" />
    
    <div class="ml-64">
      <AppTopbar @toggle-mobile-menu="toggleMobileMenu" />
      
      <main class="pt-16">
        <div class="px-8 py-6">
          <slot />
        </div>
      </main>
    </div>

    <div 
      v-if="mobileMenuOpen"
      class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
      @click="toggleMobileMenu"
    ></div>
  </div>
</template>
