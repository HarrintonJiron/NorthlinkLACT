<script setup>
import { computed } from 'vue'
import AppNavbar from './AppNavbar.vue'
import AppSidebarNav from './AppSidebarNav.vue'

const props = defineProps({
  title: String
})

const currentRoute = computed(() => {
  return window.location.pathname
})
</script>

<template>
  <div class="min-h-screen bg-[#F5F5F7]">
    <AppSidebarNav :current-route="currentRoute" />
    <AppNavbar :current-route="currentRoute" />
    
    <main class="pt-16 pl-56">
      <div class="px-6 py-8">
        <slot />
      </div>
    </main>
  </div>
</template>
