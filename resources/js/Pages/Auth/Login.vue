<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const form = useForm({
  username: '',
  password: '',
})

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head title="Iniciar sesión" />

  <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#173526] px-4 py-10">
    <div class="absolute inset-0 bg-[url('/images/login-dairy-field.png')] bg-cover bg-center" aria-hidden="true"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-[#102D20]/35 via-[#102D20]/45 to-[#071D14]/75" aria-hidden="true"></div>

    <section class="relative w-full max-w-md rounded-3xl border border-white/20 bg-[#173E2C]/90 p-8 shadow-2xl shadow-black/35 backdrop-blur-md">
      <div class="mb-8 text-center">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#D8E7C5]">Northlink LACT</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white">Iniciar sesión</h1>
        <p class="mt-2 text-sm text-[#D7E3D8]">Gestión eficiente del acopio de leche.</p>
      </div>

      <form class="space-y-5" @submit.prevent="submit">
        <div>
          <label for="username" class="mb-1.5 block text-sm font-medium text-[#F3F1E8]">Usuario</label>
          <input id="username" v-model="form.username" type="text" autocomplete="username" maxlength="50" autofocus class="h-12 w-full rounded-xl border border-white/20 bg-[#F7F3E8]/95 px-4 text-sm text-[#183326] outline-none placeholder:text-[#6E7C72] focus:border-[#B8D29A] focus:ring-2 focus:ring-[#B8D29A]/25">
          <p v-if="form.errors.username" class="mt-1.5 text-sm text-[#FFD2CC]">{{ form.errors.username }}</p>
        </div>

        <div>
          <label for="password" class="mb-1.5 block text-sm font-medium text-[#F3F1E8]">Contraseña</label>
          <input id="password" v-model="form.password" type="password" autocomplete="current-password" maxlength="255" class="h-12 w-full rounded-xl border border-white/20 bg-[#F7F3E8]/95 px-4 text-sm text-[#183326] outline-none placeholder:text-[#6E7C72] focus:border-[#B8D29A] focus:ring-2 focus:ring-[#B8D29A]/25">
          <p v-if="form.errors.password" class="mt-1.5 text-sm text-[#FFD2CC]">{{ form.errors.password }}</p>
        </div>

        <button type="submit" :disabled="form.processing" class="h-12 w-full rounded-xl bg-[#C5DBA8] text-sm font-semibold text-[#173526] shadow-lg shadow-black/15 transition hover:bg-[#D8E7C5] disabled:cursor-not-allowed disabled:opacity-60">
          {{ form.processing ? 'Verificando…' : 'Ingresar' }}
        </button>
      </form>
    </section>
  </main>
</template>
