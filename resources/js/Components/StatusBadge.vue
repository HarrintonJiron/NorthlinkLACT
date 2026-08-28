<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true
  },
  label: {
    type: String,
    required: true
  }
})

const statusClasses = computed(() => {
  const classes = {
    draft: 'bg-gray-100 text-gray-700',
    pending: 'bg-amber-100 text-amber-700',
    in_progress: 'bg-blue-100 text-blue-700',
    approved: 'bg-green-100 text-green-700',
    completed: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
    blocked: 'bg-red-100 text-red-700',
    cancelled: 'bg-gray-700 text-white',
    warning: 'bg-orange-100 text-orange-700',
    offline: 'bg-gray-100 text-gray-600',
    conflict: 'bg-red-100 text-red-700',
  }
  return classes[props.status] || classes.draft
})
</script>

<template>
  <span :class="statusClasses" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
    {{ label }}
  </span>
</template>
