<script setup>
import { ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import GoBackButton from "@/Components/GoBackButton.vue";
import DarkButton from "@/Components/DarkButton.vue";
import axios from 'axios';

const page = usePage();

const openaiQuery = ref('');
const openaiResponse = ref('');
const openaiLoading = ref(false);
const openaiSql = ref([]);
const showSql = ref(false);

function searchOpenAI() {
  if (!openaiQuery.value.trim()) return;
  
  openaiLoading.value = true;
  showSql.value = false;
  openaiSql.value = [];
  
  axios.post(route('insights.query.openai'), {
    query: openaiQuery.value
  })
    .then(res => {
      openaiLoading.value = false;
      openaiResponse.value = res.data.response;
      openaiSql.value = Array.isArray(res.data.sql) ? res.data.sql : [];
    })
    .catch(error => {
      openaiLoading.value = false;
      console.error('Error al realizar la consulta con OpenAI:', error);
    });
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text)
    .then(() => {
      alert('Respuesta copiada al portapapeles');
    })
    .catch(err => {
      console.error('Error al copiar: ', err);
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
      
      <div v-if="page.props.viteAppEnv === 'prod'">
        <div class="flex items-center gap-4 mb-4">
          <h2 class="text-xl font-semibold">Consulta Premium</h2>
          <span class="text-white text-xs px-2 py-1 rounded-full" style="background: linear-gradient(135deg, #f97316, #f59e0b, #d97706, #b45309, #d97706);">OpenAI GPT-4</span>
        </div>
        
        <div class="flex items-center gap-4">
          <input 
            type="text" 
            v-model="openaiQuery" 
            placeholder="Haz una pregunta premium..." 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @keyup.enter="searchOpenAI"
          />
          <DarkButton 
            @click="searchOpenAI" 
            :disabled="openaiLoading"
          >
            {{ openaiLoading ? 'Consultando...' : 'Consultar' }}
          </DarkButton>
        </div>
        
        <div v-if="openaiLoading" class="mt-6 border-collapse rounded-lg shadow overflow-hidden">
          <div class="text-white py-2 px-4 w-full" style="background: linear-gradient(135deg, #f97316, #f59e0b, #d97706, #b45309, #d97706);">
            <h3 class="font-medium">Procesando consulta...</h3>
          </div>
          <div class="p-8 bg-gray-100 flex justify-center items-center">
            <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2" style="border-color: #f97316;"></div>
          </div>
        </div>
        
        <div v-else-if="openaiResponse" class="mt-6 border-collapse rounded-lg shadow overflow-hidden">
          <div class="text-white py-2 px-4 w-full flex justify-between items-center" style="background: linear-gradient(135deg, #f97316, #f59e0b, #d97706, #b45309, #d97706);">
            <h3 class="font-medium">Respuesta de Mariano PREMIUM</h3>
            <button 
              @click="copyToClipboard(openaiResponse)"
              class="text-white hover:text-blue-200 text-sm"
              title="Copiar respuesta"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
              </svg>
            </button>
          </div>
          <div class="p-4 bg-gray-100">
            <p class="text-lg">{{ openaiResponse }}</p>
            <div class="mt-4" v-if="openaiSql.length">
              <button
                type="button"
                @click="showSql = !showSql"
                class="px-3 py-1.5 text-sm rounded border border-gray-400 text-gray-700 hover:bg-gray-200"
              >
                {{ showSql ? 'Ocultar SQL' : 'Mostrar SQL' }}
              </button>
              <div v-if="showSql" class="mt-3 space-y-2">
                <div
                  v-for="(sql, index) in openaiSql"
                  :key="index"
                  class="bg-gray-900 text-gray-100 rounded p-3 text-xs overflow-x-auto"
                >
                  <code>{{ sql }}</code>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>