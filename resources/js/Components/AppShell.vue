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
  <div class="min-h-screen bg-[#F5F5F7] print:bg-white">
    <div class="print:hidden">
      <AppSidebarNav :current-route="currentRoute" />
      <AppNavbar :current-route="currentRoute" />
    </div>

    <main class="pt-16 pl-56 print:pl-0 print:pt-0">
      <div class="px-6 py-8 print:p-0">
        <slot />
      </div>
    </main>
  </div>
</template>
