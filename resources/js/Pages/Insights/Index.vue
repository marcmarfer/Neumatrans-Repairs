<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import GoBackButton from "@/Components/GoBackButton.vue";
import DarkButton from "@/Components/DarkButton.vue";
import InsightTable from "@/Components/InsightTable.vue";
import axios from 'axios';
import { marked } from 'marked';

marked.setOptions({ breaks: true, gfm: true });

const page = usePage();

const openaiQuery = ref('');
const openaiResponse = ref('');
const openaiLoading = ref(false);
const openaiSql = ref([]);
const dataTables = ref([]);
const showSql = ref(false);

const models = [
  { id: 'gpt-5-mini', label: 'GPT-5 Mini' },
  { id: 'gpt-5.4', label: 'GPT-5.4' },
];
const selectedModel = ref(models[0]);

const TABLE_PLACEHOLDER = '%%TABLE_PLACEHOLDER%%';

function extractTablesFromMarkdown(md) {
  const tableRegex = /\n?\|[^\n]+\|\n\|[-: |]+\|\n(\|[^\n]+\|\n?)+/g;
  const tables = [];

  const stripped = md.replace(tableRegex, (match) => {
    const lines = match.trim().split('\n').filter(l => l.trim());
    const parseRow = (line) =>
      line.split('|').map(c => c.trim()).filter(c => c !== '');

    const headers = parseRow(lines[0]);
    const rows = lines.slice(2).map(parseRow);
    tables.push({ headers, rows });
    return '\n' + TABLE_PLACEHOLDER + '\n';
  });

  return { stripped, tables };
}

const responseParts = computed(() => {
  if (!openaiResponse.value) return [];

  const raw = openaiResponse.value;
  const hasDataTables = dataTables.value.length > 0;

  if (hasDataTables) {
    const chunks = raw.split(/\[TABLA\]/gi);
    const parts = [];
    let tableIdx = 0;

    chunks.forEach((chunk) => {
      const { stripped, tables: mdTables } = extractTablesFromMarkdown(chunk);
      const subChunks = stripped.split(TABLE_PLACEHOLDER);
      let mdIdx = 0;

      subChunks.forEach((sub) => {
        const html = marked(sub).trim();
        if (html) parts.push({ type: 'html', content: html });
        if (mdIdx < mdTables.length) {
          parts.push({ type: 'table', ...mdTables[mdIdx] });
          mdIdx++;
        }
      });

      if (tableIdx < dataTables.value.length) {
        parts.push({ type: 'datatable', index: tableIdx });
        tableIdx++;
      }
    });

    return parts;
  }

  const { stripped, tables } = extractTablesFromMarkdown(raw);
  const chunks = stripped.split(TABLE_PLACEHOLDER);
  const parts = [];
  let tableIdx = 0;

  chunks.forEach((chunk) => {
    const html = marked(chunk).trim();
    if (html) parts.push({ type: 'html', content: html });
    if (tableIdx < tables.length) {
      parts.push({ type: 'table', ...tables[tableIdx] });
      tableIdx++;
    }
  });

  return parts;
});

function searchOpenAI() {
  if (!openaiQuery.value.trim()) return;

  openaiLoading.value = true;
  showSql.value = false;
  openaiSql.value = [];
  dataTables.value = [];

  axios.post(route('insights.query.openai'), {
    query: openaiQuery.value,
    model: selectedModel.value.id,
  })
    .then(res => {
      openaiLoading.value = false;
      openaiResponse.value = res.data.response;
      openaiSql.value = Array.isArray(res.data.sql) ? res.data.sql : [];
      dataTables.value = Array.isArray(res.data.tables) ? res.data.tables : [];
    })
    .catch(error => {
      openaiLoading.value = false;
      console.error('Error al realizar la consulta con OpenAI:', error);
    });
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text)
    .then(() => alert('Respuesta copiada al portapapeles'))
    .catch(err => console.error('Error al copiar: ', err));
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
          <div class="relative inline-flex bg-gray-200 rounded-full p-0.5">
            <button
              v-for="m in models"
              :key="m.id"
              @click="selectedModel = m"
              class="relative px-3 py-1 text-xs font-medium rounded-full transition-all duration-200"
              :class="selectedModel.id === m.id
                ? 'text-white shadow-sm'
                : 'text-gray-600 hover:text-gray-800'"
              :style="selectedModel.id === m.id
                ? 'background: linear-gradient(135deg, #f97316, #f59e0b, #d97706, #b45309, #d97706)'
                : ''"
            >
              {{ m.label }}
            </button>
          </div>
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

        <div v-if="openaiLoading" class="mt-6 rounded-lg shadow overflow-hidden">
          <div class="text-white py-2 px-4 w-full" style="background: linear-gradient(135deg, #f97316, #f59e0b, #d97706, #b45309, #d97706);">
            <h3 class="font-medium">Procesando consulta...</h3>
          </div>
          <div class="p-8 bg-gray-100 flex justify-center items-center">
            <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2" style="border-color: #f97316;"></div>
          </div>
        </div>

        <div v-else-if="openaiResponse" class="mt-6 space-y-4">
          <div class="rounded-lg shadow overflow-hidden">
            <div class="text-white py-2 px-4 w-full flex justify-between items-center" style="background: linear-gradient(135deg, #f97316, #f59e0b, #d97706, #b45309, #d97706);">
              <h3 class="font-medium">Respuesta de Mariano <span class="opacity-75 text-sm">({{ selectedModel.label }})</span></h3>
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
              <template v-for="(part, i) in responseParts" :key="i">
                <div v-if="part.type === 'html'" class="prose prose-sm max-w-none [&>*:first-child]:mt-0 [&>*:last-child]:mb-0" v-html="part.content"></div>
                <InsightTable v-else-if="part.type === 'datatable'" :headers="dataTables[part.index].headers" :rows="dataTables[part.index].rows" class="my-4" />
                <InsightTable v-else :headers="part.headers" :rows="part.rows" class="my-4" />
              </template>
            </div>
          </div>

          <div v-if="openaiSql.length" class="mt-2">
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
</template>
