<script setup>
import { computed } from 'vue'
import { MapPin, Truck } from '@lucide/vue'

const props = defineProps({
  routes: {
    type: Array,
    required: true,
  },
  totalRoutes: {
    type: Number,
    default: null,
  },
})

const numberFormatter = new Intl.NumberFormat('es-NI', { maximumFractionDigits: 2 })

const routeGeometry = [
  { name: 'Dipina', x: 117, y: 308, path: 'M300 210 C270 236 246 260 212 276 C178 292 151 298 117 308' },
  { name: 'San Lucas', x: 146, y: 340, path: 'M300 210 C270 236 246 260 212 276 C188 300 166 320 146 340' },
  { name: 'Yaoska', x: 170, y: 84, path: 'M300 210 C268 184 236 158 209 129 C194 113 184 99 170 84' },
  { name: 'Kubaly', x: 433, y: 91, path: 'M300 210 C323 184 342 153 371 132 C390 118 410 105 433 91' },
  { name: 'Bethel Kubaly', x: 480, y: 118, path: 'M300 210 C323 184 342 153 371 132 C405 123 444 119 480 118' },
  { name: 'Santa María Kubaly', x: 520, y: 158, path: 'M300 210 C323 184 342 153 371 132 C413 142 473 147 520 158' },
  { name: 'Las Quebradas', x: 535, y: 238, path: 'M300 210 C345 207 381 211 425 219 C461 226 497 231 535 238' },
  { name: 'Waslalita El Naranjo', x: 491, y: 333, path: 'M300 210 C332 237 357 265 388 286 C421 309 451 322 491 333' },
  { name: 'Piedras Bolas', x: 401, y: 370, path: 'M300 210 C332 237 357 265 388 286 C388 315 394 343 401 370' },
  { name: 'Sofana', x: 307, y: 381, path: 'M300 210 C307 246 315 283 313 319 C312 341 309 360 307 381' },
  { name: 'Central #2', x: 214, y: 357, path: 'M300 210 C272 244 252 277 234 314 C226 330 220 343 214 357' },
  { name: 'El Chilamate', x: 96, y: 245, path: 'M300 210 C256 213 213 219 171 228 C143 234 121 240 96 245' },
  { name: 'San Rafael Kum', x: 92, y: 181, path: 'M300 210 C251 203 211 198 170 190 C142 184 118 181 92 181' },
  { name: 'La Tumba', x: 132, y: 134, path: 'M300 210 C262 190 232 170 197 154 C176 145 154 139 132 134' },
  { name: 'Kiawas', x: 218, y: 107, path: 'M300 210 C270 180 251 151 232 124 C228 118 223 112 218 107' },
  { name: 'Las Barandas', x: 435, y: 277, path: 'M300 210 C332 224 364 239 393 257 C408 265 421 272 435 277' },
  { name: 'Los Mangos', x: 300, y: 210, path: 'M300 210 C300 210 300 210 300 210' },
]

const normalize = (value) =>
  String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '')

const routesByName = computed(() => new Map(props.routes.map((route) => [normalize(route.name), route])))

const mappedRoutes = computed(() =>
  routeGeometry.map((point, index) => {
    const route = routesByName.value.get(normalize(point.name)) || props.routes[index] || {}

    return {
      ...route,
      ...point,
      id: route.id || `map-${index}`,
      name: route.name || point.name,
      status: route.status || 'pending',
      liters: Number(route.liters || 0),
      producers: Number(route.producers || 0),
      attended: Number(route.attended || 0),
      progress: Number(route.progress || 0),
    }
  })
)

const strokeClass = (status) => ({
  completed: 'stroke-[#34C759]',
  in_progress: 'stroke-[#007AFF]',
  pending: 'stroke-[#8E8E93]',
}[status] || 'stroke-[#8E8E93]')

const markerClass = (status) => ({
  completed: 'fill-[#34C759]',
  in_progress: 'fill-[#007AFF]',
  pending: 'fill-[#8E8E93]',
}[status] || 'fill-[#8E8E93]')
</script>

<template>
  <div class="rounded-2xl border border-[#E5E5E5] bg-white p-6 shadow-sm">
    <div class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
      <div>
        <h3 class="font-display text-lg font-semibold text-[#1D1D1F]">Mapa de rutas · Waslala</h3>
        <p class="text-xs text-[#8E8E93]">
          Rutas documentadas de acopio QUEBAR · mostrando {{ mappedRoutes.length }} de {{ props.totalRoutes ?? routes.length }}
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-3 text-xs">
        <span class="flex items-center gap-1 text-[#6E6E73]"><i class="size-3 rounded-full bg-[#34C759]" />Completada</span>
        <span class="flex items-center gap-1 text-[#6E6E73]"><i class="size-3 rounded-full bg-[#007AFF]" />En proceso</span>
        <span class="flex items-center gap-1 text-[#6E6E73]"><i class="size-3 rounded-full bg-[#8E8E93]" />Pendiente</span>
      </div>
    </div>

    <div v-if="routes.length" class="relative overflow-hidden rounded-2xl border border-[#D6E9FF] bg-gradient-to-br from-[#EFF8F1] via-[#F7FBFF] to-[#E5F1FF]">
      <iframe
        title="Mapa de Waslala, Nicaragua"
        src="https://www.google.com/maps?q=Waslala%2C%20Nicaragua&z=12&output=embed"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        class="absolute inset-0 h-full w-full border-0 grayscale-[20%] saturate-[95%]"
      />
      <div class="absolute inset-0 bg-white/30 backdrop-blur-[0.4px]" />

      <svg viewBox="0 0 620 430" class="relative h-[28rem] w-full" role="img" aria-label="Mapa gráfico de rutas de acopio en Waslala">
        <defs>
          <filter id="routeShadow" x="-20%" y="-20%" width="140%" height="140%">
            <feDropShadow dx="0" dy="6" stdDeviation="5" flood-color="#1D1D1F" flood-opacity="0.14" />
          </filter>
          <linearGradient id="terrain" x1="0" x2="1" y1="0" y2="1">
            <stop offset="0%" stop-color="#E8F8E8" />
            <stop offset="52%" stop-color="#F7FBFF" />
            <stop offset="100%" stop-color="#E5F1FF" />
          </linearGradient>
        </defs>

        <path
          d="M71 88 C138 29 250 22 342 42 C440 63 530 88 570 171 C610 252 557 343 474 382 C384 425 263 407 164 374 C76 345 32 277 36 196 C39 148 43 113 71 88 Z"
          fill="url(#terrain)"
          stroke="#B7D7C6"
          stroke-width="2"
          opacity="0.34"
        />
        <path d="M58 286 C155 240 222 262 302 229 C393 190 438 134 556 126" fill="none" stroke="#9BD0FF" stroke-width="5" stroke-linecap="round" opacity="0.45" />
        <path d="M97 119 C166 152 223 148 297 187 C362 221 427 229 525 206" fill="none" stroke="#C7E7D0" stroke-width="8" stroke-linecap="round" opacity="0.35" />
        <path d="M109 350 C173 291 219 276 300 210 C373 151 458 124 539 83" fill="none" stroke="#D6C7A1" stroke-width="4" stroke-dasharray="8 10" stroke-linecap="round" opacity="0.55" />
        <path d="M88 246 C144 237 198 226 252 218 C282 214 306 211 337 207 C398 200 462 215 548 237" fill="none" stroke="#8A6B38" stroke-width="7" stroke-linecap="round" opacity="0.18" />
        <path d="M143 342 C176 310 206 286 239 261 C267 238 286 221 300 210 C322 190 345 159 375 133 C405 107 438 94 477 92" fill="none" stroke="#8A6B38" stroke-width="7" stroke-linecap="round" opacity="0.18" />
        <path d="M303 381 C309 330 313 285 300 210 C286 172 252 137 214 106 C196 93 182 84 169 77" fill="none" stroke="#8A6B38" stroke-width="6" stroke-linecap="round" opacity="0.16" />
        <path d="M500 333 C455 319 421 303 388 286 C356 258 331 236 300 210" fill="none" stroke="#8A6B38" stroke-width="6" stroke-linecap="round" opacity="0.16" />

        <g opacity="0.32">
          <path d="M130 88 C162 70 192 68 225 82" fill="none" stroke="#91B99A" stroke-width="2" />
          <path d="M391 90 C423 75 461 76 493 98" fill="none" stroke="#91B99A" stroke-width="2" />
          <path d="M171 321 C213 300 248 305 286 332" fill="none" stroke="#91B99A" stroke-width="2" />
          <path d="M410 325 C444 304 478 305 513 330" fill="none" stroke="#91B99A" stroke-width="2" />
        </g>

        <text x="300" y="190" text-anchor="middle" class="fill-[#1D1D1F] text-[15px] font-bold">Waslala</text>
        <text x="300" y="207" text-anchor="middle" class="fill-[#6E6E73] text-[10px]">Centro operativo</text>
        <text x="206" y="260" text-anchor="middle" class="fill-[#6E6E73] text-[9px] font-semibold">Tramo Dipina - San Lucas</text>
        <text x="420" y="128" text-anchor="middle" class="fill-[#6E6E73] text-[9px] font-semibold">Corredor Kubaly</text>
        <text x="436" y="307" text-anchor="middle" class="fill-[#6E6E73] text-[9px] font-semibold">El Naranjo</text>

        <g v-for="route in mappedRoutes" :key="`line-${route.id}`" filter="url(#routeShadow)">
          <path
            v-if="route.name !== 'Los Mangos'"
            :d="route.path"
            fill="none"
            stroke-width="4"
            stroke-linecap="round"
            stroke-dasharray="10 7"
            :class="strokeClass(route.status)"
            opacity="0.82"
          />
        </g>

        <g v-for="route in mappedRoutes" :key="route.id" class="group">
          <circle
            :cx="route.x"
            :cy="route.y"
            r="12"
            class="stroke-white stroke-[4] transition-transform duration-200 group-hover:scale-125"
            :class="markerClass(route.status)"
          />
          <circle
            v-if="route.status === 'in_progress'"
            :cx="route.x"
            :cy="route.y"
            r="18"
            fill="none"
            stroke="#007AFF"
            stroke-width="2"
            opacity="0.25"
          />
          <text
            :x="route.x"
            :y="route.y + (route.y < 80 ? 32 : -22)"
            text-anchor="middle"
            class="pointer-events-none fill-[#1D1D1F] text-[10px] font-bold"
          >
            {{ route.name }}
          </text>
          <title>{{ route.name }} · {{ numberFormatter.format(route.liters) }} L · {{ route.attended }}/{{ route.producers }} productores</title>
        </g>

        <g transform="translate(300 210)">
          <circle r="18" fill="#1D1D1F" stroke="white" stroke-width="5" />
          <circle r="7" fill="#FFD60A" />
        </g>
      </svg>

      <div class="absolute left-4 top-4 rounded-2xl border border-white/70 bg-white/85 px-4 py-3 shadow-sm backdrop-blur">
        <p class="text-xs font-bold uppercase tracking-wide text-[#007AFF]">QUEBAR · Los Mangos</p>
        <p class="mt-1 text-xs text-[#6E6E73]">Mapa visual referencial para demo</p>
      </div>

      <div v-if="routes.some((route) => route.status === 'in_progress')" class="absolute left-[48%] top-[48%] animate-pulse rounded-full bg-[#007AFF] p-2 shadow-lg">
        <Truck class="size-5 text-white" />
      </div>
    </div>

    <div v-else class="flex h-80 flex-col items-center justify-center gap-2 rounded-xl bg-[#F5F5F7] text-center">
      <MapPin class="size-8 text-[#8E8E93]" />
      <p class="font-medium text-[#1D1D1F]">No hay rutas activas</p>
      <p class="text-sm text-[#8E8E93]">Activa o registra una ruta para verla aquí.</p>
    </div>

    <div v-if="routes.length" class="mt-4 grid max-h-52 grid-cols-1 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
      <div v-for="route in mappedRoutes" :key="`summary-${route.id}`" class="flex items-center justify-between rounded-lg bg-[#F5F5F7] p-2">
        <div class="flex min-w-0 items-center gap-2">
          <span class="size-2 shrink-0 rounded-full" :class="route.status === 'completed' ? 'bg-[#34C759]' : route.status === 'in_progress' ? 'bg-[#007AFF]' : 'bg-[#8E8E93]'" />
          <span class="truncate text-sm text-[#1D1D1F]">{{ route.name }}</span>
        </div>
        <span class="shrink-0 text-xs text-[#6E6E73]">{{ numberFormatter.format(route.liters) }} L</span>
      </div>
    </div>
  </div>
</template>
