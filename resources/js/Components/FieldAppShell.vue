<script setup>
import { onMounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Cloud, Droplets, LogOut } from '@lucide/vue'
import { clearPrivateOfflineData, ensureOfflineOwner } from '@/offline/sumniOffline'

defineProps({
  showBackToRoutes: {
    type: Boolean,
    default: false,
  },
})

const page = usePage()
onMounted(() => ensureOfflineOwner(page.props.auth?.user?.username).catch(() => {}))
const logout = async () => {
  await clearPrivateOfflineData().catch(() => {})
  router.post('/logout')
}
</script>

<template>
  <div class="min-h-[100dvh] bg-[#F3F6F4] text-[#17221C]">
    <header class="sticky top-0 z-40 border-b border-[#DCE5DF] bg-white/95 backdrop-blur">
      <div class="mx-auto flex h-16 w-full max-w-3xl items-center justify-between gap-3 px-4 sm:px-6">
        <Link href="/sumni" class="flex min-w-0 items-center gap-3">
          <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-[#176B45] text-white shadow-sm">
            <Droplets class="size-5" />
          </span>
          <span class="min-w-0">
            <span class="block truncate text-base font-extrabold leading-tight">Lácteos Quebar</span>
            <span class="block truncate text-[11px] font-semibold uppercase tracking-[0.12em] text-[#688075]">Acopio de campo</span>
          </span>
        </Link>

        <div class="flex items-center gap-2">
          <span class="hidden items-center gap-1.5 rounded-full bg-[#EAF5EE] px-3 py-2 text-xs font-semibold text-[#176B45] sm:inline-flex">
            <Cloud class="size-3.5" />
            Nube Northlink
          </span>
          <button
            type="button"
            aria-label="Cerrar sesión"
            title="Cerrar sesión"
            class="flex size-10 items-center justify-center rounded-xl border border-[#DCE5DF] bg-white text-[#52665C] active:bg-[#EEF3F0]"
            @click="logout"
          >
            <LogOut class="size-4.5" />
          </button>
        </div>
      </div>
    </header>

    <main class="mx-auto w-full max-w-3xl px-3 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-4 sm:px-6 sm:pt-6">
      <slot />
    </main>

    <footer class="px-4 pb-5 pt-2 text-center text-[10px] font-medium text-[#829087]">
      Northlink Microsystem · {{ page.props.auth?.user?.name || 'Usuario de campo' }}
    </footer>
  </div>
</template>
