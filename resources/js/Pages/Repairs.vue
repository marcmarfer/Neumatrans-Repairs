<script setup>
import { Head } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    repairs: {
        type: Array,
        required: true
    }
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'repair_type_id', label: 'Tipo de Reparación' },
    { key: 'vehicle_id', label: 'ID Vehículo' },
    { key: 'observations', label: 'Observaciones' },
    { key: 'status', label: 'Estado' },
    { key: 'started_at', label: 'Fecha de Inicio' },
    { key: 'created_at', label: 'Fecha de Creación' }
];
</script>

<template>
    <Head title="Reparaciones" />

    <div class="container mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            Reparaciones
        </h1>

        <DataTable 
            :data="repairs"
            :columns="columns"
            :items-per-page="10"
        />
    </div>
</template>
