<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { FileText } from '@lucide/vue'
import AppNavbar from './AppNavbar.vue'
import AppSidebarNav from './AppSidebarNav.vue'

defineProps({
  title: String
})

const page = usePage()
const currentRoute = computed(() => page.url.split('?')[0])

const reportModules = [
  { prefix: '/sumni', module: 'sumni', label: 'PDF Sumni' },
  { prefix: '/routes', module: 'routes', label: 'PDF Rutas' },
  { prefix: '/ruteros', module: 'ruteros', label: 'PDF Ruteros' },
  { prefix: '/collections', module: 'collections', label: 'PDF Acopio' },
  { prefix: '/producers', module: 'producers', label: 'PDF Productores' },
  { prefix: '/finanzas', module: 'finances', label: 'PDF Finanzas' },
  { prefix: '/payment-sheets', module: 'payment-sheets', label: 'PDF Planillas' },
  { prefix: '/payments', module: 'payments', label: 'PDF Pagos' },
  { prefix: '/production', module: 'production', label: 'PDF Producción' },
  { prefix: '/dispatches', module: 'production', label: 'PDF Remisiones' },
  { prefix: '/inventory', module: 'inventory', label: 'PDF Inventario' },
  { prefix: '/employees', module: 'personnel', label: 'PDF Personal' },
  { prefix: '/reports', module: 'reports', label: 'PDF Reportes' },
  { prefix: '/settings', module: 'settings', label: 'PDF Configuración' },
]

const currentReport = computed(() => {
  if (currentRoute.value === '/') {
    return { module: 'dashboard', label: 'PDF Dashboard' }
  }

  return reportModules.find((item) =>
    currentRoute.value === item.prefix || currentRoute.value.startsWith(`${item.prefix}/`)
  )
})
</script>

<template>
  <div class="min-h-screen bg-[#F5F5F7]">
    <AppSidebarNav :current-route="currentRoute" />
    <AppNavbar :current-route="currentRoute" />

    <main class="pt-16 pl-56">
      <div class="px-6 py-8">
        <div v-if="currentReport" class="mb-5 flex justify-end">
          <a
            :href="`/module-reports/${currentReport.module}.pdf`"
            target="_blank"
            class="inline-flex items-center gap-2 rounded-2xl border border-[#D6E9FF] bg-white px-4 py-2.5 text-sm font-semibold text-[#007AFF] shadow-sm transition hover:-translate-y-0.5 hover:border-[#007AFF]/40 hover:shadow-md"
          >
            <FileText class="size-4" />
            {{ currentReport.label }}
          </a>
        </div>
        <slot />
      </div>
    </main>
  </div>
</template>
