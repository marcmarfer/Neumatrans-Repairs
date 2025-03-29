<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import GoBackButton from "@/Components/GoBackButton.vue";
import DarkButton from "@/Components/DarkButton.vue";
import axios from 'axios';

const query = ref('');
const response = ref('');
const loading = ref(false);

function searchQuery() {
  if (!query.value.trim()) return;
  
  loading.value = true;
  
  axios.post(route('insights.query'), {
    query: query.value
  })
    .then(res => {
      loading.value = false;
      response.value = res.data.response;
    })
    .catch(error => {
      loading.value = false;
      console.error('Error al realizar la consulta:', error);
    });
}
</script>

<template>
    <Head title="Estadísticas"></Head>
    <GoBackButton type="button" @click="router.visit(route('dashboard.show'))">
      Volver
    </GoBackButton>
    <div class="container mx-auto py-8 px-4">
      <h1 class="text-2xl font-bold text-gray-800 mb-6">Estadísticas</h1>
      
      <div class="flex items-center gap-4">
        <input 
          type="text" 
          v-model="query" 
          placeholder="Haz una pregunta sobre el taller..." 
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          @keyup.enter="searchQuery"
        />
        <DarkButton 
          @click="searchQuery" 
          :disabled="loading"
        >
          {{ loading ? 'Preguntando...' : 'Preguntar' }}
        </DarkButton>
      </div>
      
      <div v-if="response" class="mt-6 border-collapse rounded-lg shadow overflow-hidden">
        <div class="bg-red-500 text-white py-2 px-4 w-full">
          <h3 class="font-medium">Respuesta de Manolito</h3>
        </div>
        <div class="p-4 bg-gray-100">
          <p class="text-lg">{{ response }}</p>
        </div>
      </div>
    </div>
</template>