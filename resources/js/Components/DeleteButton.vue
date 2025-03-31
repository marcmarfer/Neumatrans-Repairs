<template>
    <button
      :disabled="disabled"
      class="bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <slot />
    </button>
  </template>
  
  <script setup>
  defineProps({
    disabled: {
      type: Boolean,
      default: false,
    },
  });
  </script>
  