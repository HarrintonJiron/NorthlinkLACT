<script setup>
import AppShell from '../Components/AppShell.vue'
import { Construction, ArrowLeft } from '@lucide/vue'
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <AppShell>
    <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
      <div class="bg-[#E8F4F8] p-8 rounded-full mb-6">
        <Construction class="w-24 h-24 text-[#1E3A5F]" />
      </div>
      
      <h1 class="text-3xl font-display font-bold text-gray-900 mb-4">
        Estamos trabajando en ello
      </h1>
      
      <p class="text-gray-500 max-w-md mb-8">
        Esta sección está en desarrollo. Pronto estará disponible para mejorar tu experiencia con Northlink LACT.
      </p>
      
      <Link 
        href="/"
        class="inline-flex items-center px-6 py-3 bg-[#1E3A5F] text-white rounded-lg hover:bg-[#2D4A6F] transition-colors"
      >
        <ArrowLeft class="w-4 h-4 mr-2" />
        Volver al inicio
      </Link>
    </div>
  </AppShell>
</template>
