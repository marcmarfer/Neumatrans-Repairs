<script setup>
import { Head } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    clients: {
        type: Array,
        required: true
    }
});

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'DNI', label: 'DNI' },
    { key: 'name', label: 'Nombre' },
    { key: 'email', label: 'Email' },
    { key: 'telephone', label: 'Teléfono' },
    { key: 'city', label: 'Ciudad' },
    { key: 'postal_code', label: 'Código Postal' },
    { key: 'registered_at', label: 'Fecha de Registro' }
];
</script>

<template>
    <Head title="Clientes" />

    <div class="container mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            Clientes
        </h1>

        <DataTable 
            :data="clients"
            :columns="columns"
            :items-per-page="10"
        />
    </div>
</template>