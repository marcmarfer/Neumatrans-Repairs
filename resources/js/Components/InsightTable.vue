<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  headers: { type: Array, required: true },
  rows: { type: Array, required: true },
});

const currentPage = ref(1);
const perPage = 10;

watch(() => props.rows, () => { currentPage.value = 1; });

const totalPages = computed(() => Math.ceil(props.rows.length / perPage));
const startRow = computed(() => (currentPage.value - 1) * perPage + 1);
const endRow = computed(() => Math.min(currentPage.value * perPage, props.rows.length));

const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return props.rows.slice(start, start + perPage);
});

const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

  const pages = [1];
  if (current > 3) pages.push('...');
  for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) pages.push(i);
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

function goToPage(page) {
  if (page >= 1 && page <= totalPages.value) currentPage.value = page;
}

function exportCsv() {
  const header = props.headers.join(';');
  const csvRows = props.rows.map(row =>
    row.map(cell => {
      const str = String(cell ?? '');
      return str.includes(';') || str.includes('"') || str.includes('\n')
        ? '"' + str.replace(/"/g, '""') + '"'
        : str;
    }).join(';')
  ).join('\n');

  const bom = '\uFEFF';
  const blob = new Blob([bom + header + '\n' + csvRows], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = 'estadisticas.csv';
  link.click();
  URL.revokeObjectURL(url);
}
</script>

<template>
  <div class="overflow-hidden bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
      <table class="min-w-full">
        <thead>
          <tr style="background: linear-gradient(135deg, #f97316, #f59e0b, #d97706, #b45309, #d97706);">
            <th
              v-for="(header, i) in headers"
              :key="i"
              class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider"
            >
              {{ header }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="(row, ri) in paginatedRows"
            :key="ri"
            class="hover:bg-gray-50"
          >
            <td
              v-for="(cell, ci) in row"
              :key="ci"
              class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"
            >
              {{ cell }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
      <span class="text-sm text-gray-600">
        Mostrando <b>{{ startRow }}</b>–<b>{{ endRow }}</b> de <b>{{ rows.length }}</b>
      </span>

      <div class="flex items-center gap-3">
        <div v-if="totalPages > 1" class="flex items-center gap-1">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-2 py-1 text-sm rounded transition-colors"
            :class="currentPage === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-200'"
          >&laquo;</button>

          <template v-for="(p, i) in visiblePages" :key="i">
            <span v-if="p === '...'" class="px-1 text-sm text-gray-400">...</span>
            <button
              v-else
              @click="goToPage(p)"
              class="px-3 py-1 text-sm rounded font-medium transition-colors"
              :class="p === currentPage ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200'"
              :style="p === currentPage ? 'background: linear-gradient(135deg, #f97316, #f59e0b, #d97706)' : ''"
            >{{ p }}</button>
          </template>

          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="px-2 py-1 text-sm rounded transition-colors"
            :class="currentPage === totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-200'"
          >&raquo;</button>
        </div>

        <button
          @click="exportCsv"
          class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white rounded-md shadow-sm transition-colors hover:brightness-110"
          style="background: linear-gradient(135deg, #f97316, #f59e0b, #d97706);"
          title="Descargar CSV"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
          CSV
        </button>
      </div>
    </div>
  </div>
</template>
