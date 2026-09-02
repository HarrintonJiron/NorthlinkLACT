<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppNavbar from './AppNavbar.vue'
import AppSidebarNav from './AppSidebarNav.vue'

defineProps({
  title: String
})

const page = usePage()
const currentRoute = computed(() => page.url.split('?')[0])
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
