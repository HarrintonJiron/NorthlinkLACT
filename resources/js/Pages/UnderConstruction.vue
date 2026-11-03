<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppShell from '../Components/AppShell.vue'
import {
  ArrowLeft,
  BarChart3,
  CheckCircle2,
  ClipboardList,
  Clock3,
  FileText,
  PackageCheck,
  ShieldCheck,
  Sparkles,
  TrendingUp,
} from '@lucide/vue'

const props = defineProps({
  module: {
    type: String,
    default: 'general',
  },
})

const modules = {
  'payment-sheets': {
    eyebrow: 'Finanzas · productores',
    title: 'Planillas de pago',
    description: 'Vista de avance para preparar liquidaciones por semana, ruta, productor y estado de revisión.',
    progress: 48,
    tone: 'from-[#007AFF] to-[#5AC8FA]',
    metrics: [
      ['Planillas previstas', '12'],
      ['Productores vinculados', '331'],
      ['Estados diseñados', '6'],
    ],
    features: [
      'Resumen por productor, ruta y semana de pago.',
      'Estados visuales: calculada, revisada, aprobada, pagada y conciliada.',
      'Reporte PDF de avance para reunión con cliente.',
    ],
    next: [
      'Conectar reglas reales de precio, bonificación y penalización.',
      'Bloqueo de planillas aprobadas contra cambios directos.',
      'Historial auditable por responsable.',
    ],
    records: [
      ['Semana 36 · Ruta Norte', 'C$ 184,250.00', 'Revisión', '71 productores'],
      ['Semana 36 · Ruta Sur', 'C$ 132,890.50', 'Calculada', '54 productores'],
      ['Semana 35 · Consolidado', 'C$ 318,410.75', 'Aprobada', '128 productores'],
    ],
    pipeline: ['Recolectar litros', 'Aplicar precio', 'Revisar ajustes', 'Aprobar pago'],
  },
  payments: {
    eyebrow: 'Finanzas · tesorería',
    title: 'Pagos',
    description: 'Pantalla preliminar para visualizar programación, aprobación y conciliación de pagos.',
    progress: 42,
    tone: 'from-[#34C759] to-[#5AC8FA]',
    metrics: [
      ['Pagos demo', '36'],
      ['Canales', '5'],
      ['Alertas', '4'],
    ],
    features: [
      'Bandeja visual de pagos pendientes, programados y conciliados.',
      'Tarjetas de control para flujo de caja operativo.',
      'Reporte PDF de avance para validación gerencial.',
    ],
    next: [
      'Conectar autorizaciones y doble aprobación.',
      'Soportar comprobantes y referencias bancarias.',
      'Conciliar contra movimientos financieros reales.',
    ],
    records: [
      ['Pago productores · Semana 36', 'C$ 317,140.50', 'Programado', 'Transferencia'],
      ['Combustible rutas', 'C$ 24,800.00', 'Pendiente', 'Efectivo'],
      ['Mantenimiento vehículo R-03', 'C$ 9,450.00', 'Conciliado', 'Cheque'],
    ],
    pipeline: ['Solicitado', 'Aprobado', 'Programado', 'Pagado'],
  },
  production: {
    eyebrow: 'Operaciones · planta',
    title: 'Producción',
    description: 'Módulo visual para mostrar trazabilidad futura de lotes, rendimiento y costos de producción.',
    progress: 35,
    tone: 'from-[#AF52DE] to-[#5AC8FA]',
    metrics: [
      ['Líneas previstas', '4'],
      ['Indicadores', '9'],
      ['Etapas', '5'],
    ],
    features: [
      'Panel ejecutivo para leche recibida, transformada y rendimiento.',
      'Roadmap visual de lote, proceso, merma y producto terminado.',
      'Reporte PDF de avance para presentar el alcance futuro.',
    ],
    next: [
      'Definir fórmulas oficiales de rendimiento por producto.',
      'Conectar consumo de inventario e ingresos de producto terminado.',
      'Cerrar trazabilidad lote a lote.',
    ],
    records: [
      ['Lote QBR-260902-A', '1,240 L', 'En proceso', 'Queso fresco'],
      ['Lote QBR-260901-C', '890 L', 'Empacado', 'Cuajada'],
      ['Lote QBR-260831-B', '1,510 L', 'Cerrado', 'Crema'],
    ],
    pipeline: ['Recepción', 'Pasteurización', 'Transformación', 'Empaque'],
  },
  reports: {
    eyebrow: 'Sistema · gerencia',
    title: 'Centro de reportes',
    description: 'Catálogo visual de reportes disponibles por módulo para enseñar avances al cliente.',
    progress: 55,
    tone: 'from-[#FF9500] to-[#FFD60A]',
    metrics: [
      ['PDF base', '13'],
      ['Módulos cubiertos', '13'],
      ['Pendientes críticos', '5'],
    ],
    features: [
      'Accesos rápidos a reportes por módulo.',
      'Separación clara entre avance visual y reporte oficial auditable.',
      'Diseño listo para ampliar con filtros por fecha, ruta, planta y productor.',
    ],
    next: [
      'Agregar filtros y exportación real por periodo.',
      'Crear plantillas oficiales con numeración y firmas.',
      'Validar cada reporte contra datos históricos.',
    ],
    records: [
      ['Reporte de acopio diario', 'PDF', 'Disponible demo', 'Ruta / fecha'],
      ['Reporte financiero semanal', 'PDF', 'Disponible demo', 'Semana / categoría'],
      ['Reporte inventario crítico', 'PDF', 'Disponible demo', 'Stock mínimo'],
    ],
    pipeline: ['Seleccionar módulo', 'Aplicar filtros', 'Generar PDF', 'Auditar'],
  },
  general: {
    eyebrow: 'Northlink Quebar',
    title: 'Módulo en preparación',
    description: 'Esta sección ya tiene una maqueta visual para mostrar dirección, alcance y próximos pasos.',
    progress: 30,
    tone: 'from-[#1E3A5F] to-[#007AFF]',
    metrics: [
      ['Componentes', '8'],
      ['Pantallas', '1'],
      ['Reporte demo', '1'],
    ],
    features: [
      'Interfaz limpia para mostrar avance sin confundirlo con producción.',
      'Tarjetas ejecutivas para explicar alcance al cliente.',
      'Reporte PDF base de avance.',
    ],
    next: [
      'Confirmar reglas finales de negocio.',
      'Conectar datos reales y permisos granulares.',
      'Agregar pruebas y auditoría antes de producción.',
    ],
    records: [
      ['Módulo general', '—', 'Vista demo', 'Pendiente alcance'],
      ['Reporte avance', 'PDF', 'Disponible', 'Cliente'],
      ['Validación final', 'QA', 'Pendiente', 'Producción'],
    ],
    pipeline: ['Diseño', 'Datos', 'Permisos', 'Pruebas'],
  },
}

const current = computed(() => modules[props.module] || modules.general)

const statusClass = (status) => {
  const normalized = String(status || '').toLowerCase()

  if (['aprobada', 'conciliado', 'cerrado', 'disponible demo', 'disponible'].some((value) => normalized.includes(value))) {
    return 'bg-[#E8F8E8] text-[#1D7A32]'
  }

  if (['revisión', 'programado', 'en proceso', 'empacado', 'vista demo'].some((value) => normalized.includes(value))) {
    return 'bg-[#E5F1FF] text-[#007AFF]'
  }

  return 'bg-[#FFF4E5] text-[#FF9500]'
}
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <section class="relative overflow-hidden rounded-[32px] bg-white p-8 shadow-sm ring-1 ring-[#E5E5E5]">
        <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-[#E5F1FF] blur-3xl" />
        <div class="absolute right-20 top-10 h-32 w-32 rounded-full bg-[#F5F5F7] blur-2xl" />

        <div class="relative grid gap-8 lg:grid-cols-[1fr_320px] lg:items-center">
          <div>
            <div class="mb-5 inline-flex items-center gap-2 rounded-full bg-[#F5F5F7] px-4 py-2 text-sm font-semibold text-[#007AFF]">
              <Sparkles class="size-4" />
              {{ current.eyebrow }} · Vista demo
            </div>

            <h1 class="max-w-3xl text-4xl font-display font-bold tracking-tight text-[#1D1D1F] lg:text-5xl">
              {{ current.title }}
            </h1>

            <p class="mt-4 max-w-2xl text-lg leading-8 text-[#6E6E73]">
              {{ current.description }}
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
              <a
                :href="`/module-reports/${module}.pdf`"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-2xl bg-[#007AFF] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-[#007AFF]/20 transition hover:-translate-y-0.5"
              >
                <FileText class="size-4" />
                Ver reporte PDF
              </a>

              <Link
                href="/"
                class="inline-flex items-center gap-2 rounded-2xl border border-[#E5E5E5] bg-white px-5 py-3 text-sm font-bold text-[#1D1D1F] transition hover:-translate-y-0.5 hover:shadow-sm"
              >
                <ArrowLeft class="size-4" />
                Volver al dashboard
              </Link>
            </div>
          </div>

          <div class="rounded-[28px] border border-[#E5E5E5] bg-[#F5F5F7] p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-[#6E6E73]">Avance visual</p>
                <p class="text-4xl font-bold text-[#1D1D1F]">{{ current.progress }}%</p>
              </div>
              <div :class="`flex size-16 items-center justify-center rounded-3xl bg-gradient-to-br ${current.tone} text-white shadow-lg`">
                <BarChart3 class="size-8" />
              </div>
            </div>

            <div class="mt-5 h-3 overflow-hidden rounded-full bg-white">
              <div
                class="h-full rounded-full bg-gradient-to-r from-[#007AFF] to-[#5AC8FA]"
                :style="{ width: `${current.progress}%` }"
              />
            </div>

            <p class="mt-4 text-sm leading-6 text-[#6E6E73]">
              Prototipo visual para presentar avance. Los botones no ejecutan procesos críticos todavía.
            </p>
          </div>
        </div>
      </section>

      <section class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <article class="overflow-hidden rounded-[28px] border border-[#E5E5E5] bg-white shadow-sm">
          <div class="flex flex-col gap-3 border-b border-[#E5E5E5] p-5 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-xl font-bold text-[#1D1D1F]">Bandeja operativa demo</h2>
              <p class="text-sm text-[#8E8E93]">Datos de muestra para que el cliente visualice el flujo.</p>
            </div>
            <div class="flex gap-2">
              <button
                type="button"
                class="rounded-2xl bg-[#F5F5F7] px-4 py-2 text-sm font-semibold text-[#6E6E73]"
              >
                Filtrar
              </button>
              <button
                type="button"
                class="rounded-2xl bg-[#1D1D1F] px-4 py-2 text-sm font-semibold text-white"
              >
                Nuevo registro
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-[#F5F5F7] text-left text-xs uppercase tracking-wide text-[#8E8E93]">
                <tr>
                  <th class="px-5 py-3 font-semibold">Registro</th>
                  <th class="px-4 py-3 font-semibold">Valor</th>
                  <th class="px-4 py-3 font-semibold">Estado</th>
                  <th class="px-4 py-3 font-semibold">Detalle</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#E5E5E5]">
                <tr
                  v-for="record in current.records"
                  :key="record[0]"
                  class="transition hover:bg-[#F5F5F7]"
                >
                  <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                      <div :class="`flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br ${current.tone} text-white`">
                        <TrendingUp class="size-5" />
                      </div>
                      <div>
                        <p class="font-semibold text-[#1D1D1F]">{{ record[0] }}</p>
                        <p class="text-xs text-[#8E8E93]">Código demo automático</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-4 font-semibold tabular-nums text-[#1D1D1F]">{{ record[1] }}</td>
                  <td class="px-4 py-4">
                    <span :class="['inline-flex rounded-full px-3 py-1 text-xs font-bold', statusClass(record[2])]">
                      {{ record[2] }}
                    </span>
                  </td>
                  <td class="px-4 py-4 text-[#6E6E73]">{{ record[3] }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <article class="rounded-[28px] border border-[#E5E5E5] bg-white p-6 shadow-sm">
          <h2 class="text-xl font-bold text-[#1D1D1F]">Flujo propuesto</h2>
          <p class="mt-1 text-sm text-[#8E8E93]">Secuencia visual del módulo para explicar al cliente.</p>

          <div class="mt-6 space-y-4">
            <div
              v-for="(step, index) in current.pipeline"
              :key="step"
              class="flex items-center gap-4"
            >
              <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-[#E5F1FF] text-sm font-bold text-[#007AFF]">
                {{ index + 1 }}
              </div>
              <div class="flex-1 rounded-2xl bg-[#F5F5F7] px-4 py-3">
                <p class="font-semibold text-[#1D1D1F]">{{ step }}</p>
                <p class="text-xs text-[#8E8E93]">Pendiente de lógica real y permisos.</p>
              </div>
            </div>
          </div>
        </article>
      </section>

      <section class="grid gap-4 md:grid-cols-3">
        <article
          v-for="[label, value] in current.metrics"
          :key="label"
          class="rounded-[24px] border border-[#E5E5E5] bg-white p-5 shadow-sm"
        >
          <p class="text-sm font-semibold text-[#8E8E93]">{{ label }}</p>
          <p class="mt-2 text-3xl font-bold text-[#1D1D1F]">{{ value }}</p>
        </article>
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <article class="rounded-[28px] border border-[#E5E5E5] bg-white p-6 shadow-sm">
          <div class="mb-5 flex items-center gap-3">
            <div class="rounded-2xl bg-[#E8F8E8] p-3 text-[#1D7A32]">
              <PackageCheck class="size-5" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-[#1D1D1F]">Lo que se puede mostrar</h2>
              <p class="text-sm text-[#8E8E93]">Avance visual para reunión con cliente.</p>
            </div>
          </div>

          <ul class="space-y-3">
            <li
              v-for="feature in current.features"
              :key="feature"
              class="flex gap-3 rounded-2xl bg-[#F5F5F7] p-4 text-sm text-[#1D1D1F]"
            >
              <CheckCircle2 class="mt-0.5 size-5 shrink-0 text-[#34C759]" />
              <span>{{ feature }}</span>
            </li>
          </ul>
        </article>

        <article class="rounded-[28px] border border-[#E5E5E5] bg-white p-6 shadow-sm">
          <div class="mb-5 flex items-center gap-3">
            <div class="rounded-2xl bg-[#FFF4E5] p-3 text-[#FF9500]">
              <ClipboardList class="size-5" />
            </div>
            <div>
              <h2 class="text-xl font-bold text-[#1D1D1F]">Pendiente para producción</h2>
              <p class="text-sm text-[#8E8E93]">Para no vender humo técnico, mi parte favorita.</p>
            </div>
          </div>

          <ul class="space-y-3">
            <li
              v-for="item in current.next"
              :key="item"
              class="flex gap-3 rounded-2xl bg-[#F5F5F7] p-4 text-sm text-[#1D1D1F]"
            >
              <Clock3 class="mt-0.5 size-5 shrink-0 text-[#FF9500]" />
              <span>{{ item }}</span>
            </li>
          </ul>
        </article>
      </section>

      <div class="rounded-[24px] border border-[#D6E9FF] bg-[#F5FBFF] p-5 text-sm leading-6 text-[#1E3A5F]">
        <div class="flex gap-3">
          <ShieldCheck class="size-5 shrink-0 text-[#007AFF]" />
          <p>
            Nota técnica: esta pantalla comunica avance de desarrollo. Para declarar producción se requiere completar lógica,
            permisos, auditoría, pruebas automatizadas, conciliación de datos y aprobación de negocio.
          </p>
        </div>
      </div>
    </div>
  </AppShell>
</template>
