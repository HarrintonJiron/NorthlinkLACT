<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { AlertTriangle, Home } from '@lucide/vue'
defineProps({ status: Number, title: String, message: String })
</script>
<template><Head :title="title" /><main class="flex min-h-screen items-center justify-center bg-slate-100 p-6"><section class="w-full max-w-lg rounded-3xl bg-white p-8 text-center shadow-xl"><div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-700"><AlertTriangle class="size-8" /></div><p class="mt-5 text-sm font-bold uppercase tracking-[.2em] text-slate-400">Error {{ status }}</p><h1 class="mt-2 text-3xl font-bold text-slate-950">{{ title }}</h1><p class="mt-3 text-slate-600">{{ message }}</p><Link href="/" class="mt-6 inline-flex min-h-11 items-center gap-2 rounded-xl bg-blue-600 px-5 font-bold text-white"><Home class="size-4" />Volver al inicio</Link></section></main></template>
