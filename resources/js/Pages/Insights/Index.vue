<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import GoBackButton from "@/Components/GoBackButton.vue";
import DarkButton from "@/Components/DarkButton.vue";
import axios from 'axios';

const openaiQuery = ref('');
const openaiResponse = ref('');
const openaiLoading = ref(false);

const geminiQuery = ref('');
const geminiResponse = ref('');
const geminiLoading = ref(false);

function searchOpenAI() {
  if (!openaiQuery.value.trim()) return;
  
  openaiLoading.value = true;
  
  axios.post(route('insights.query.openai'), {
    query: openaiQuery.value
  })
    .then(res => {
      openaiLoading.value = false;
      openaiResponse.value = res.data.response;
    })
    .catch(error => {
      openaiLoading.value = false;
      console.error('Error al realizar la consulta con OpenAI:', error);
    });
}

function searchGemini() {
  if (!geminiQuery.value.trim()) return;
  
  geminiLoading.value = true;
  
  axios.post(route('insights.query.gemini'), {
    query: geminiQuery.value
  })
    .then(res => {
      geminiLoading.value = false;
      geminiResponse.value = res.data.response;
    })
    .catch(error => {
      geminiLoading.value = false;
      console.error('Error al realizar la consulta con Gemini:', error);
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
      
      <div class="bg-gray-100 p-4 rounded-lg mb-6 border border-gray-300">
        <h2 class="text-lg font-bold text-gray-800 mb-2">Información sobre Consultas</h2>
        <p class="text-sm text-gray-700">
          Dispones de dos opciones para analizar tus datos:
        </p>
        <ul class="mt-2 text-sm text-gray-700 list-disc list-inside space-y-1">
          <li><span class="font-semibold">Consulta Premium (OpenAI):</span> Ofrece respuestas más completas y precisas. Tiene un coste asociado por uso. (No disponible en el entorno demo)</li>
          <li><span class="font-semibold">Consulta Gratuita (Gemini):</span> Alternativa sin coste, pero con respuestas potencialmente menos detalladas.</li>
        </ul>
      </div>
      
      <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
          <h2 class="text-xl font-semibold">Consulta Gratuita</h2>
          <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">Gemini</span>
        </div>
        
        <div class="flex items-center gap-4">
          <input 
            type="text" 
            v-model="geminiQuery" 
            placeholder="Haz una pregunta sobre el taller..." 
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @keyup.enter="searchGemini"
          />
          <DarkButton 
            @click="searchGemini" 
            :disabled="geminiLoading"
          >
            {{ geminiLoading ? 'Consultando...' : 'Consultar' }}
          </DarkButton>
        </div>
        
        <div v-if="geminiLoading" class="mt-6 border-collapse rounded-lg shadow overflow-hidden">
          <div class="bg-red-500 text-white py-2 px-4 w-full">
            <h3 class="font-medium">Procesando consulta...</h3>
          </div>
          <div class="p-8 bg-gray-100 flex justify-center items-center">
            <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-red-500"></div>
          </div>
        </div>
        
        <div v-else-if="geminiResponse" class="mt-6 border-collapse rounded-lg shadow overflow-hidden">
          <div class="bg-red-500 text-white py-2 px-4 w-full flex justify-between items-center">
            <h3 class="font-medium">Respuesta de Mariano</h3>
            <button 
              @click="copyToClipboard(geminiResponse)"
              class="text-white hover:text-red-200 text-sm"
              title="Copiar respuesta"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
              </svg>
            </button>
          </div>
          <div class="p-4 bg-gray-100">
            <p class="text-lg">{{ geminiResponse }}</p>
          </div>
        </div>
      </div>
      
      <div v-if="$page.props.viteAppEnv === 'prod'">
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
          </div>
        </div>
      </div>
    </div>
</template>