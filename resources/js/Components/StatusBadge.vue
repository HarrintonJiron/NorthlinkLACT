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
    draft: 'bg-[#F5F5F7] text-[#6E6E73]',
    pending: 'bg-[#FFF4E5] text-[#FF9500]',
    in_progress: 'bg-[#E5F1FF] text-[#007AFF]',
    approved: 'bg-[#E8F8E8] text-[#34C759]',
    completed: 'bg-[#E8F8E8] text-[#34C759]',
    rejected: 'bg-[#FFE5E5] text-[#FF3B30]',
    blocked: 'bg-[#FFE5E5] text-[#FF3B30]',
    cancelled: 'bg-[#F5F5F7] text-[#8E8E93]',
    warning: 'bg-[#FFF4E5] text-[#FF9500]',
    offline: 'bg-[#F5F5F7] text-[#8E8E93]',
    conflict: 'bg-[#FFE5E5] text-[#FF3B30]',
  }
  return classes[props.status] || classes.draft
})
</script>

<template>
  <span :class="statusClasses" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold">
    {{ label }}
  </span>
</template>
