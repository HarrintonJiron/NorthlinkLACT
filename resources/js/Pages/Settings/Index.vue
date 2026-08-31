<script setup>
import AppShell from '../../Components/AppShell.vue'
import { Head, Link } from '@inertiajs/vue3'
import {
  ArrowRight,
  Building2,
  KeyRound,
  MapPinned,
  Settings,
  ShieldCheck,
  Users,
} from '@lucide/vue'

const options = [
  {
    title: 'Usuarios y acceso',
    description: 'Crea cuentas y asigna roles, empresas y plantas.',
    href: '/settings/users',
    icon: Users,
    accent: 'bg-[#E8F2FF] text-[#007AFF]',
    enabled: true,
  },
  {
    title: 'Roles y permisos',
    description: 'Define qué puede hacer cada perfil del sistema.',
    icon: ShieldCheck,
    accent: 'bg-[#F2EAFE] text-[#AF52DE]',
  },
  {
    title: 'Empresa',
    description: 'Administra datos fiscales y preferencias generales.',
    icon: Building2,
    accent: 'bg-[#E9F8EE] text-[#34C759]',
  },
  {
    title: 'Plantas y sedes',
    description: 'Organiza ubicaciones y centros operativos.',
    icon: MapPinned,
    accent: 'bg-[#FFF3E6] text-[#FF9500]',
  },
]
</script>

<template>
  <AppShell>
    <Head title="Configuración" />

    <div class="mx-auto max-w-6xl">
      <div class="mb-6 flex items-start gap-3">
        <div class="flex size-10 items-center justify-center rounded-xl bg-[#1D1D1F] text-white shadow-sm">
          <Settings class="size-5" />
        </div>
        <div>
          <h1 class="font-display text-2xl font-bold tracking-tight text-[#1D1D1F]">
            Configuración
          </h1>
          <p class="mt-1 text-sm text-[#6E6E73]">
            Administra el acceso y la estructura de Northlink LACT.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <template v-for="option in options" :key="option.title">
          <Link
            v-if="option.enabled"
            :href="option.href"
            class="group flex min-h-32 items-start gap-4 rounded-2xl border border-[#E5E5E5] bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#007AFF]/40 hover:shadow-md"
          >
            <div :class="['flex size-11 shrink-0 items-center justify-center rounded-xl', option.accent]">
              <component :is="option.icon" class="size-5" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-base font-semibold text-[#1D1D1F]">
                  {{ option.title }}
                </h2>
                <ArrowRight class="size-4 text-[#8E8E93] transition group-hover:translate-x-0.5 group-hover:text-[#007AFF]" />
              </div>
              <p class="mt-1.5 text-sm leading-5 text-[#6E6E73]">
                {{ option.description }}
              </p>
              <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-[#007AFF]">
                Administrar
              </span>
            </div>
          </Link>

          <div
            v-else
            class="flex min-h-32 items-start gap-4 rounded-2xl border border-[#E5E5E5] bg-white/70 p-5"
          >
            <div :class="['flex size-11 shrink-0 items-center justify-center rounded-xl', option.accent]">
              <component :is="option.icon" class="size-5" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-base font-semibold text-[#1D1D1F]">
                  {{ option.title }}
                </h2>
                <KeyRound class="size-4 text-[#C7C7CC]" />
              </div>
              <p class="mt-1.5 text-sm leading-5 text-[#8E8E93]">
                {{ option.description }}
              </p>
              <span class="mt-3 inline-flex rounded-full bg-[#F5F5F7] px-2 py-1 text-[11px] font-medium text-[#8E8E93]">
                Próximamente
              </span>
            </div>
          </div>
        </template>
      </div>
    </div>
  </AppShell>
</template>
