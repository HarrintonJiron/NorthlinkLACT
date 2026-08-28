<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  icon: {
    type: Object,
    required: true
  },
  actionLabel: String,
  actionHref: String
})
</script>

<template>
  <div class="text-center py-12">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
      <component :is="icon" class="w-8 h-8 text-gray-400" />
    </div>
    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ title }}</h3>
    <p class="text-gray-500 max-w-sm mx-auto mb-6">{{ description }}</p>
    <a 
      v-if="actionLabel && actionHref"
      :href="actionHref"
      class="inline-flex items-center px-4 py-2 bg-[#1E3A5F] text-white rounded-lg hover:bg-[#2D4A6F] transition-colors"
    >
      {{ actionLabel }}
    </a>
  </div>
</template>
