<script setup>
const props = defineProps({
    vehicles: Array
});
</script>

<template>
    <div class="vehicles" v-for="vehicle in vehicles" :key="vehicle.id">
        <span>name: {{ vehicle.client_id }}, plate: {{ vehicle.plate_number }}</span>
    </div>
</template>