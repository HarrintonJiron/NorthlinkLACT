<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  subtitle: String,
  trend: {
    type: Object,
    default: null
  },
  icon: {
    type: Object,
    required: true
  },
  color: {
    type: String,
    default: 'blue'
  }
})

const colorClasses = computed(() => {
  const colors = {
    blue: 'bg-blue-50 text-blue-600',
    green: 'bg-green-50 text-green-600',
    amber: 'bg-amber-50 text-amber-600',
    red: 'bg-red-50 text-red-600',
  }
  return colors[props.color] || colors.blue
})

const trendIcon = computed(() => {
  if (!props.trend) return null
  return props.trend.direction === 'up' ? '↑' : '↓'
})

const trendColor = computed(() => {
  if (!props.trend) return ''
  return props.trend.direction === 'up' ? 'text-green-600' : 'text-red-600'
})
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <p class="text-sm font-medium text-gray-500">{{ title }}</p>
        <p class="mt-2 text-3xl font-display font-bold text-gray-900">{{ value }}</p>
        <p v-if="subtitle" class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
        <div v-if="trend" class="mt-2 flex items-center">
          <span :class="trendColor" class="text-sm font-medium">
            {{ trendIcon }} {{ trend.value }}%
          </span>
          <span class="ml-2 text-sm text-gray-500">vs período anterior</span>
        </div>
      </div>
      <div :class="colorClasses" class="p-3 rounded-lg">
        <component :is="icon" class="w-6 h-6" />
      </div>
    </div>
  </div>
</template>
