<script setup>
import { ref } from 'vue'
import AppShell from '../../../Components/AppShell.vue'
import { Link, router } from '@inertiajs/vue3'
import { Plus, Search, User, MapPin, Phone, Edit, Trash2 } from '@lucide/vue'
import ProducerModal from '../../../Components/ProducerModal.vue'

const props = defineProps({
  producers: Object,
  routes: Array
})

const searchQuery = ref('')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedProducer = ref(null)

const openCreateModal = () => {
  showCreateModal.value = true
}

const openEditModal = (producer) => {
  selectedProducer.value = producer
  showEditModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
}

const closeEditModal = () => {
  showEditModal.value = false
  selectedProducer.value = null
}

const handleCreate = (form) => {
  form.post('/producers', {
    onSuccess: () => {
      closeCreateModal()
    }
  })
}

const handleUpdate = (form) => {
  form.put(`/producers/${selectedProducer.value.id}`, {
    onSuccess: () => {
      closeEditModal()
    }
  })
}

const deleteProducer = (id) => {
  if (confirm('¿Estás seguro de eliminar este productor?')) {
    router.delete(`/producers/${id}`)
  }
}
</script>

<template>
  <AppShell>
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-display font-bold text-[#1D1D1F]">Productores</h1>
          <p class="text-[#6E6E73] mt-1">Gestiona los productores de leche</p>
        </div>
        <div>
          <button 
            @click="openCreateModal"
            class="inline-flex items-center px-4 py-2 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] transition-colors text-sm font-medium shadow-sm"
          >
            <Plus class="w-4 h-4 mr-2" />
            Nuevo productor
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-[#E5E5E5] p-6 shadow-sm">
      <!-- Search -->
      <div class="flex items-center space-x-4 mb-6">
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
          <input 
            type="text" 
            v-model="searchQuery"
            placeholder="Buscar productor..."
            class="pl-10 w-full bg-[#F5F5F7] border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-[#007AFF]/50 py-2.5 px-3 text-sm text-[#1D1D1F] placeholder-[#8E8E93]"
          />
        </div>
      </div>

      <!-- Producers List -->
      <div class="space-y-3">
        <div 
          v-for="producer in producers.data"
          :key="producer.id"
          class="flex items-center justify-between p-4 bg-[#F5F5F7] rounded-xl hover:bg-[#E8F0FE] transition-colors"
        >
          <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-gradient-to-br from-[#007AFF] to-[#5AC8FA] rounded-full flex items-center justify-center text-white font-semibold shadow-sm">
              {{ producer.full_name.charAt(0) }}
            </div>
            <div>
              <p class="font-medium text-[#1D1D1F]">{{ producer.full_name }}</p>
              <div class="flex items-center space-x-3 text-xs text-[#8E8E93] mt-1">
                <span v-if="producer.identity_number" class="flex items-center">
                  <User class="w-3 h-3 mr-1" />
                  {{ producer.identity_number }}
                </span>
                <span v-if="producer.phone" class="flex items-center">
                  <Phone class="w-3 h-3 mr-1" />
                  {{ producer.phone }}
                </span>
                <span v-if="producer.community" class="flex items-center">
                  <MapPin class="w-3 h-3 mr-1" />
                  {{ producer.community }}
                </span>
              </div>
            </div>
          </div>
          
          <div class="flex items-center space-x-3">
            <div v-if="producer.active_assignment" class="text-right mr-4">
              <p class="text-xs text-[#8E8E93]">Ruta</p>
              <p class="text-sm font-medium text-[#1D1D1F]">{{ producer.active_assignment.route?.name }}</p>
            </div>
            <div class="flex items-center space-x-1">
              <button 
                @click="openEditModal(producer)"
                class="p-2 rounded-lg hover:bg-[#E5E5E5] transition-colors text-[#8E8E93]"
              >
                <Edit class="w-4 h-4" />
              </button>
              <button 
                @click="deleteProducer(producer.id)"
                class="p-2 rounded-lg hover:bg-[#FFE5E5] transition-colors text-[#8E8E93] hover:text-[#FF3B30]"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!producers.data || producers.data.length === 0" class="text-center py-12">
          <User class="w-16 h-16 text-[#8E8E93] mx-auto mb-4" />
          <p class="text-[#6E6E73] text-lg mb-2">No hay productores registrados</p>
          <p class="text-[#8E8E93] text-sm mb-4">Comienza creando el primer productor</p>
          <button 
            @click="openCreateModal"
            class="inline-flex items-center px-4 py-2 bg-[#007AFF] text-white rounded-xl hover:bg-[#0056CC] transition-colors text-sm font-medium shadow-sm"
          >
            <Plus class="w-4 h-4 mr-2" />
            Crear productor
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="producers.links && producers.links.length > 3" class="flex items-center justify-between mt-6 pt-4 border-t border-[#E5E5E5]">
        <p class="text-sm text-[#8E8E93]">
          Mostrando {{ producers.from }} a {{ producers.to }} de {{ producers.total }} productores
        </p>
        <div class="flex items-center space-x-2">
          <Link 
            v-for="(link, index) in producers.links" 
            :key="index"
            :href="link.url"
            :class="[
              'px-3 py-1 text-sm rounded-lg transition-colors',
              link.active 
                ? 'bg-[#007AFF] text-white' 
                : 'text-[#6E6E73] hover:bg-[#F5F5F7]'
            ]"
            v-html="link.label"
          />
        </div>
      </div>
    </div>

    <!-- Modals -->
    <ProducerModal 
      :show="showCreateModal"
      :routes="routes"
      @close="closeCreateModal"
      @submit="handleCreate"
    />

    <ProducerModal 
      :show="showEditModal"
      :routes="routes"
      :producer="selectedProducer"
      @close="closeEditModal"
      @submit="handleUpdate"
    />
  </AppShell>
</template>
