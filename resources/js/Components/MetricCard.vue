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
    blue: 'from-[#007AFF] to-[#5AC8FA]',
    green: 'from-[#34C759] to-[#30D158]',
    orange: 'from-[#FF9500] to-[#FF9F0A]',
    red: 'from-[#FF3B30] to-[#FF453A]',
    purple: 'from-[#AF52DE] to-[#BF5AF2]',
    pink: 'from-[#FF2D55] to-[#FF375F]',
  }
  return colors[props.color] || colors.blue
})

const trendIcon = computed(() => {
  if (!props.trend) return null
  return props.trend.direction === 'up' ? '↑' : '↓'
})

const trendColor = computed(() => {
  if (!props.trend) return ''
  return props.trend.direction === 'up' ? 'text-[#34C759]' : 'text-[#FF3B30]'
})
</script>

<template>
  <div class="bg-white rounded-2xl p-4 hover:shadow-lg transition-all duration-300 border border-[#E5E5E5]">
    <div class="flex items-start justify-between">
      <div class="flex-1 min-w-0">
        <p class="text-xs font-medium text-[#8E8E93] truncate">{{ title }}</p>
        <p class="mt-1 text-2xl font-display font-bold text-[#1D1D1F] truncate">{{ value }}</p>
        <p v-if="subtitle" class="text-xs text-[#8E8E93] mt-0.5 truncate">{{ subtitle }}</p>
        <div v-if="trend" class="mt-1.5 flex items-center">
          <span :class="trendColor" class="text-xs font-semibold">
            {{ trendIcon }} {{ trend.value }}%
          </span>
        </div>
      </div>
      <div :class="colorClasses" class="p-2.5 rounded-xl bg-gradient-to-br shadow-sm flex-shrink-0 ml-3">
        <component :is="icon" class="w-5 h-5 text-white" />
      </div>
    </div>
  </div>
</template>
