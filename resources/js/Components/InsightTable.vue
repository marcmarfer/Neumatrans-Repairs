<script setup>
import { computed } from 'vue';

const props = defineProps({
  headers: { type: Array, required: true },
  rows: { type: Array, required: true },
});
</script>

<template>
  <div class="overflow-x-auto bg-white rounded-lg shadow">
    <table class="min-w-full border-collapse">
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
      <tbody class="bg-white">
        <tr
          v-for="(row, ri) in rows"
          :key="ri"
          class="hover:bg-gray-50 border-b border-gray-200"
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
</template>
