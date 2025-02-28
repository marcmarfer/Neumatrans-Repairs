<script setup>
import { Head } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    vehicles: {
        type: Array,
        required: true
    }
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'client.name', label: 'Cliente' },
    { key: 'plate_number', label: 'Matrícula' },
    { key: 'brand', label: 'Marca' },
    { key: 'model', label: 'Modelo' },
    { key: 'VIN', label: 'VIN' },
    { key: 'motor_type', label: 'Tipo de Motor' },
    { key: 'added_at', label: 'Fecha de Alta' }
];
</script>

<template>
    <Head title="Vehículos" />

    <div class="container mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            Vehículos
        </h1>

        <DataTable 
            :data="vehicles"
            :columns="columns"
            :items-per-page="10"
        />
    </div>
</template>