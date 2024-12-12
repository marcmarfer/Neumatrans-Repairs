<script setup>
const props = defineProps({
    clients: Array
});
</script>

<template>
    <div class="clients" v-for="client in clients" :key="client.id">
        <span>name: {{ client.name }}, email: {{ client.email }}, vehicle count: {{ client.vehicle_count }}</span>
    </div>
</template>