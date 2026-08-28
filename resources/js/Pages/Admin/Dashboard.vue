<script setup>
import AppShell from '../../Components/AppShell.vue'
import MetricCard from '../../Components/MetricCard.vue'
import StatusBadge from '../../Components/StatusBadge.vue'
import EmptyState from '../../Components/EmptyState.vue'
import { 
  Droplets, 
  Users, 
  MapPin, 
  DollarSign, 
  Package, 
  AlertTriangle,
  TrendingUp,
  CheckCircle,
  Clock,
  AlertCircle,
  FileText
} from '@lucide/vue'

const metrics = [
  {
    title: 'Litros acopiados hoy',
    value: '12,450',
    subtitle: 'de 15,000 programados',
    trend: { direction: 'up', value: 8.5 },
    icon: Droplets,
    color: 'blue'
  },
  {
    title: 'Productores atendidos',
    value: '87',
    subtitle: 'de 95 programados',
    trend: { direction: 'up', value: 3.2 },
    icon: Users,
    color: 'green'
  },
  {
    title: 'Rutas completadas',
    value: '8/12',
    subtitle: '4 en proceso',
    icon: MapPin,
    color: 'amber'
  },
  {
    title: 'Pagos estimados semana',
    value: 'C$ 450,000',
    subtitle: 'C$ 38,000 pendientes',
    icon: DollarSign,
    color: 'blue'
  },
  {
    title: 'Producción diaria',
    value: '11,200 L',
    subtitle: 'queso, crema, suero',
    icon: Package,
    color: 'green'
  },
  {
    title: 'Alertas operativas',
    value: '3',
    subtitle: 'requieren atención',
    icon: AlertTriangle,
    color: 'red'
  }
]

const routesStatus = [
  { name: 'Ruta Matagalpa', status: 'completed', liters: 2850, producers: 12 },
  { name: 'Ruta Estelí', status: 'in_progress', liters: 1920, producers: 8 },
  { name: 'Ruta Jinotega', status: 'pending', liters: 0, producers: 15 },
  { name: 'Ruta Sébaco', status: 'completed', liters: 3100, producers: 18 },
]

const pendingOperations = [
  { 
    type: 'Planilla pendiente', 
    description: 'Ruta Matagalpa - 28/08/2026',
    priority: 'high',
    responsible: 'María González',
    date: 'Hoy',
    action: 'Aprobar'
  },
  { 
    type: 'Anticipo sin rendir', 
    description: 'Juan Pérez - C$ 5,000',
    priority: 'medium',
    responsible: 'Carlos Rodríguez',
    date: 'Hace 2 días',
    action: 'Revisar'
  },
  { 
    type: 'Inventario bajo', 
    description: 'Leche cruda - 500L mínimo',
    priority: 'high',
    responsible: 'Ana López',
    date: 'Hoy',
    action: 'Reabastecer'
  },
]

const recentActivity = [
  { event: 'Acopio registrado', details: 'Ruta Estelí - 2,450 litros', time: 'Hace 10 min' },
  { event: 'Planilla aprobada', details: 'Ruta Matagalpa - C$ 28,500', time: 'Hace 25 min' },
  { event: 'Pago ejecutado', details: 'Productor José Martínez - C$ 4,200', time: 'Hace 1 hora' },
  { event: 'Ajuste de inventario', details: 'Leche cruda - +200 litros', time: 'Hace 2 horas' },
]
</script>

<template>
  <AppShell>
    <div class="mb-8">
      <h1 class="text-2xl font-display font-bold text-gray-900">Dashboard</h1>
      <p class="text-gray-500 mt-1">Resumen de operaciones de hoy</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <MetricCard
        v-for="metric in metrics"
        :key="metric.title"
        :title="metric.title"
        :value="metric.value"
        :subtitle="metric.subtitle"
        :trend="metric.trend"
        :icon="metric.icon"
        :color="metric.color"
      />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-display font-semibold text-gray-900">Rendimiento de acopio</h2>
          <div class="flex items-center space-x-2">
            <select class="text-sm border border-gray-300 rounded-md px-3 py-1.5">
              <option>Últimos 7 días</option>
              <option>Últimos 30 días</option>
              <option>Este mes</option>
            </select>
          </div>
        </div>
        <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
          <div class="text-center text-gray-400">
            <TrendingUp class="w-12 h-12 mx-auto mb-2" />
            <p>Gráfico de rendimiento</p>
            <p class="text-sm">Datos de demostración</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-display font-semibold text-gray-900 mb-4">Estado de rutas</h2>
        <div class="space-y-3">
          <div 
            v-for="route in routesStatus"
            :key="route.name"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
          >
            <div class="flex-1">
              <p class="font-medium text-gray-900">{{ route.name }}</p>
              <p class="text-sm text-gray-500">{{ route.producers }} productores</p>
            </div>
            <div class="text-right">
              <StatusBadge 
                :status="route.status"
                :label="route.status === 'completed' ? 'Completada' : route.status === 'in_progress' ? 'En proceso' : 'Pendiente'"
              />
              <p class="text-sm text-gray-600 mt-1">{{ route.liters }} L</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-display font-semibold text-gray-900 mb-4">Operaciones pendientes</h2>
        <div class="space-y-4">
          <div 
            v-for="op in pendingOperations"
            :key="op.description"
            class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:border-[#2D5A3D] transition-colors"
          >
            <div :class="op.priority === 'high' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'" class="p-2 rounded-lg">
              <AlertCircle v-if="op.priority === 'high'" class="w-4 h-4" />
              <Clock v-else class="w-4 h-4" />
            </div>
            <div class="flex-1">
              <p class="font-medium text-gray-900">{{ op.type }}</p>
              <p class="text-sm text-gray-500">{{ op.description }}</p>
              <div class="flex items-center mt-2 text-xs text-gray-400">
                <span>{{ op.responsible }}</span>
                <span class="mx-2">•</span>
                <span>{{ op.date }}</span>
              </div>
            </div>
            <button class="text-sm text-[#1E3A5F] hover:text-[#2D5A3D] font-medium">
              {{ op.action }}
            </button>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-display font-semibold text-gray-900 mb-4">Actividad reciente</h2>
        <div class="space-y-4">
          <div 
            v-for="activity in recentActivity"
            :key="activity.details"
            class="flex items-start space-x-3"
          >
            <div class="bg-green-100 text-green-600 p-2 rounded-lg">
              <CheckCircle class="w-4 h-4" />
            </div>
            <div class="flex-1">
              <p class="font-medium text-gray-900">{{ activity.event }}</p>
              <p class="text-sm text-gray-500">{{ activity.details }}</p>
              <p class="text-xs text-gray-400 mt-1">{{ activity.time }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>
