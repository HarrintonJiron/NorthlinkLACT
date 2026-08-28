<script setup>
import AppShell from '../../Components/AppShell.vue'
import MetricCard from '../../Components/MetricCard.vue'
import StatusBadge from '../../Components/StatusBadge.vue'
import EmptyState from '../../Components/EmptyState.vue'
import RouteMap from '../../Components/RouteMap.vue'
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
  FileText,
  ArrowUpRight,
  ArrowDownRight,
  Calendar,
  Thermometer,
  Truck
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
    color: 'orange'
  },
  {
    title: 'Pagos estimados semana',
    value: 'C$ 450,000',
    subtitle: 'C$ 38,000 pendientes',
    icon: DollarSign,
    color: 'purple'
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
  { name: 'Ruta Matagalpa', status: 'completed', liters: 2850, producers: 12, progress: 100 },
  { name: 'Ruta Estelí', status: 'in_progress', liters: 1920, producers: 8, progress: 65 },
  { name: 'Ruta Jinotega', status: 'pending', liters: 0, producers: 15, progress: 0 },
  { name: 'Ruta Sébaco', status: 'completed', liters: 3100, producers: 18, progress: 100 },
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
  { event: 'Acopio registrado', details: 'Ruta Estelí - 2,450 litros', time: 'Hace 10 min', icon: Droplets },
  { event: 'Planilla aprobada', details: 'Ruta Matagalpa - C$ 28,500', time: 'Hace 25 min', icon: CheckCircle },
  { event: 'Pago ejecutado', details: 'Productor José Martínez - C$ 4,200', time: 'Hace 1 hora', icon: DollarSign },
  { event: 'Ajuste de inventario', details: 'Leche cruda - +200 litros', time: 'Hace 2 horas', icon: Package },
  { event: 'Nueva ruta creada', details: 'Ruta Boaco - 12 productores', time: 'Hace 3 horas', icon: MapPin },
]

const weeklyData = [
  { day: 'Lun', liters: 10500, target: 12000 },
  { day: 'Mar', liters: 11200, target: 12000 },
  { day: 'Mié', liters: 9800, target: 12000 },
  { day: 'Jue', liters: 12450, target: 12000 },
  { day: 'Vie', liters: 11800, target: 12000 },
  { day: 'Sáb', liters: 8500, target: 10000 },
  { day: 'Dom', liters: 6200, target: 8000 },
]

const qualityMetrics = [
  { metric: 'Temperatura promedio', value: '4.2°C', status: 'good', icon: Thermometer },
  { metric: 'Acidez promedio', value: '6.8°D', status: 'good', icon: FileText },
  { metric: 'Grasa promedio', value: '3.5%', status: 'good', icon: Package },
  { metric: 'Rechazos', value: '0.5%', status: 'warning', icon: AlertTriangle },
]
</script>

<template>
  <AppShell>
    <div class="mb-6">
      <h1 class="text-3xl font-display font-bold text-[#1D1D1F]">Dashboard</h1>
      <p class="text-[#6E6E73] mt-1">Resumen de operaciones de hoy</p>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <!-- Route Map -->
      <div class="lg:col-span-2">
        <RouteMap />
      </div>

      <!-- Route Status -->
      <div class="bg-white rounded-2xl border border-[#E5E5E5] p-5 shadow-sm">
        <h2 class="text-base font-display font-semibold text-[#1D1D1F] mb-4">Estado de Rutas</h2>
        <div class="space-y-3">
          <div 
            v-for="route in routesStatus"
            :key="route.name"
            class="p-3 bg-[#F5F5F7] rounded-xl"
          >
            <div class="flex items-center justify-between mb-2">
              <p class="font-medium text-[#1D1D1F] text-sm">{{ route.name }}</p>
              <StatusBadge 
                :status="route.status"
                :label="route.status === 'completed' ? 'Completada' : route.status === 'in_progress' ? 'En proceso' : 'Pendiente'"
              />
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-[#8E8E93]">{{ route.producers }} productores</span>
              <span class="text-[#6E6E73]">{{ route.liters }} L</span>
            </div>
            <div class="mt-2 h-1.5 bg-[#E5E5E5] rounded-full overflow-hidden">
              <div 
                :class="[
                  'h-full rounded-full transition-all duration-300',
                  route.status === 'completed' ? 'bg-[#34C759]' : route.status === 'in_progress' ? 'bg-[#007AFF]' : 'bg-[#8E8E93]'
                ]"
                :style="{ width: `${route.progress}%` }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Weekly Performance Chart -->
      <div class="bg-white rounded-2xl border border-[#E5E5E5] p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-base font-display font-semibold text-[#1D1D1F]">Rendimiento Semanal</h2>
          <select class="text-xs bg-[#F5F5F7] border-none rounded-lg px-3 py-1.5 text-[#1D1D1F] focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50">
            <option>Esta semana</option>
            <option>Semana pasada</option>
            <option>Este mes</option>
          </select>
        </div>
        
        <div class="h-56 flex items-end justify-between space-x-2">
          <div 
            v-for="(data, index) in weeklyData" 
            :key="data.day"
            class="flex-1 flex flex-col items-center"
          >
            <div class="w-full flex flex-col items-center">
              <div 
                class="w-full bg-[#007AFF] rounded-t-lg transition-all duration-300 hover:bg-[#0056CC]"
                :style="{ height: `${(data.liters / 13000) * 100}%` }"
              ></div>
              <div 
                class="w-full bg-[#E5E5E5] rounded-b-lg mt-1"
                :style="{ height: `${(data.target / 13000) * 100}%` }"
              ></div>
            </div>
            <span class="text-xs text-[#8E8E93] mt-2">{{ data.day }}</span>
          </div>
        </div>
        
        <div class="flex items-center justify-center space-x-6 mt-4 text-xs">
          <div class="flex items-center">
            <div class="w-2.5 h-2.5 bg-[#007AFF] rounded mr-2"></div>
            <span class="text-[#6E6E73]">Real</span>
          </div>
          <div class="flex items-center">
            <div class="w-2.5 h-2.5 bg-[#E5E5E5] rounded mr-2"></div>
            <span class="text-[#6E6E73]">Objetivo</span>
          </div>
        </div>
      </div>

      <!-- Quality Metrics -->
      <div class="bg-white rounded-2xl border border-[#E5E5E5] p-5 shadow-sm">
        <h2 class="text-base font-display font-semibold text-[#1D1D1F] mb-4">Calidad de Leche</h2>
        <div class="grid grid-cols-2 gap-3">
          <div 
            v-for="metric in qualityMetrics"
            :key="metric.metric"
            class="p-3 bg-[#F5F5F7] rounded-xl"
          >
            <div class="flex items-center justify-between mb-2">
              <component :is="metric.icon" class="w-4 h-4 text-[#8E8E93]" />
              <div 
                :class="[
                  'w-1.5 h-1.5 rounded-full',
                  metric.status === 'good' ? 'bg-[#34C759]' : metric.status === 'warning' ? 'bg-[#FF9500]' : 'bg-[#FF3B30]'
                ]"
              ></div>
            </div>
            <p class="text-xs text-[#8E8E93] mb-1">{{ metric.metric }}</p>
            <p class="text-lg font-display font-bold text-[#1D1D1F]">{{ metric.value }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Pending Operations -->
      <div class="bg-white rounded-2xl border border-[#E5E5E5] p-5 shadow-sm">
        <h2 class="text-base font-display font-semibold text-[#1D1D1F] mb-4">Operaciones Pendientes</h2>
        <div class="space-y-3">
          <div 
            v-for="op in pendingOperations"
            :key="op.description"
            class="flex items-start space-x-3 p-3 border border-[#E5E5E5] rounded-xl hover:border-[#007AFF] transition-colors"
          >
            <div :class="op.priority === 'high' ? 'bg-[#FFE5E5] text-[#FF3B30]' : 'bg-[#FFF4E5] text-[#FF9500]'" class="p-2 rounded-xl">
              <AlertCircle v-if="op.priority === 'high'" class="w-4 h-4" />
              <Clock v-else class="w-4 h-4" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-[#1D1D1F] text-sm">{{ op.type }}</p>
              <p class="text-sm text-[#8E8E93] truncate">{{ op.description }}</p>
              <div class="flex items-center mt-1.5 text-xs text-[#8E8E93]">
                <span>{{ op.responsible }}</span>
                <span class="mx-2">•</span>
                <span>{{ op.date }}</span>
              </div>
            </div>
            <button class="text-xs text-[#007AFF] hover:text-[#0056CC] font-semibold whitespace-nowrap">
              {{ op.action }}
            </button>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white rounded-2xl border border-[#E5E5E5] p-5 shadow-sm">
        <h2 class="text-base font-display font-semibold text-[#1D1D1F] mb-4">Actividad Reciente</h2>
        <div class="space-y-2">
          <div 
            v-for="activity in recentActivity"
            :key="activity.details"
            class="flex items-start space-x-3 p-2.5 hover:bg-[#F5F5F7] rounded-xl transition-colors"
          >
            <div class="bg-[#E8F8E8] text-[#34C759] p-2 rounded-xl flex-shrink-0">
              <component :is="activity.icon" class="w-4 h-4" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-[#1D1D1F] text-sm">{{ activity.event }}</p>
              <p class="text-sm text-[#8E8E93] truncate">{{ activity.details }}</p>
              <p class="text-xs text-[#8E8E93] mt-0.5">{{ activity.time }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>
