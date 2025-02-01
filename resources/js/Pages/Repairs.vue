<script setup>
const props = defineProps({
    repairs: Array
});
</script>

<template>
    <div class="repairs" v-for="repair in repairs" :key="repair.id">
        <span>id: {{ repair.id }}, state: {{ repair.status }}</span>
    </div>
</template>
